<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use Horde_Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Mail_Transport_Null;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_Addresses;
use Horde_Mime_Headers_Date;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Headers_Subject;
use Horde_Mime_Mail;
use Horde_Mime_Mdn;
use Horde_Smtp_Exception;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Events\SaveDraftEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\DataUri\DataUriParser;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class MailTransmission implements IMailTransmission {
	private const RETRIABLE_CODES = [
		Horde_Smtp_Exception::INSUFFICIENT_STORAGE,
		Horde_Smtp_Exception::OVERQUOTA,
		Horde_Smtp_Exception::LOGIN_REQUIREAUTHENTICATION,
	];

	public function __construct(
		private IMAPClientFactory $imapClientFactory,
		private SmtpClientFactory $smtpClientFactory,
		private IEventDispatcher $eventDispatcher,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private LoggerInterface $logger,
		private PerformanceLogger $performanceLogger,
		private AliasesService $aliasesService,
		private TransmissionService $transmissionService
	) {
	}

	public function sendMessage(Account $account, LocalMessage $localMessage): void {
		$to = $this->transmissionService->getAddressList($localMessage, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($localMessage, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($localMessage, Recipient::TYPE_BCC);
		$attachments = $this->transmissionService->getAttachments($localMessage);

		$alias = null;
		if ($localMessage->getAliasId() !== null) {
			$alias = $this->aliasesService->find($localMessage->getAliasId(), $account->getUserId());
		}
		$fromEmail = $alias ? $alias->getAlias() : $account->getEMailAddress();
		$from = new AddressList([
			Address::fromRaw($account->getName(), $fromEmail),
		]);

		$attachmentParts = [];
		foreach ($attachments as $attachment) {
			$part = $this->transmissionService->handleAttachment($account, $attachment);
			if ($part !== null) {
				$attachmentParts[] = $part;
			}
		}

		$transport = $this->smtpClientFactory->create($account);
		// build mime body
		$headers = [
			'From' => $from->first()->toHorde(),
			'To' => $to->toHorde(),
			'Cc' => $cc->toHorde(),
			'Bcc' => $bcc->toHorde(),
			'Subject' => $localMessage->getSubject(),
		];

		if (($inReplyTo = $localMessage->getInReplyToMessageId()) !== null) {
			$headers['References'] = $inReplyTo;
			$headers['In-Reply-To'] = $inReplyTo;
		}

		if ($localMessage->getRequestMdn()) {
			$headers[Horde_Mime_Mdn::MDN_HEADER] = $from->first()->toHorde();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);

		$mimeMessage = new MimeMessage(
			new DataUriParser()
		);
		$mimePart = $mimeMessage->build(
			$localMessage->isHtml(),
			$localMessage->getBody(),
			$attachmentParts
		);

		// TODO: add smimeEncrypt check if implemented
		try {
			$mimePart = $this->transmissionService->getSignMimePart($localMessage, $account, $mimePart);
			$mimePart = $this->transmissionService->getEncryptMimePart($localMessage, $to, $cc, $bcc, $account, $mimePart);
		} catch (ServiceException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		$mail->setBasePart($mimePart);

		// Send the message
		try {
			$mail->send($transport, false, false);
			// FIXME: Fix encoding inconsistencies upstream and use stream=false instead
			//        - stream=false defaults to 7bit quoted-printable
			//        - stream=true defaults to 8bit
			//        However, the headers are taken from the base part and always indicate the 8bit
			//		  encoding.
			$localMessage->setRaw(stream_get_contents($mail->getRaw(true)));
			$localMessage->setStatus(LocalMessage::STATUS_RAW);
		} catch (Horde_Mime_Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			if (in_array($e->getCode(), self::RETRIABLE_CODES, true)) {
				$localMessage->setStatus(LocalMessage::STATUS_SMPT_SEND_FAIL);
				return;
			}
			$localMessage->setStatus(LocalMessage::STATUS_ERROR);
			return;
		}

		$this->eventDispatcher->dispatchTyped(
			new MessageSentEvent($account, $localMessage)
		);
	}

	public function saveLocalDraft(Account $account, LocalMessage $message): void {
		$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);
		$attachments = $this->transmissionService->getAttachments($message);

		$perfLogger = $this->performanceLogger->start('save local draft');

		$imapMessage = $account->newMessage();
		$imapMessage->setTo($to);
		$imapMessage->setSubject($message->getSubject());
		$from = new AddressList([
			Address::fromRaw($account->getName(), $account->getEMailAddress()),
		]);
		$imapMessage->setFrom($from);
		$imapMessage->setCC($cc);
		$imapMessage->setBcc($bcc);
		$imapMessage->setContent($message->getBody());

		foreach ($attachments as $attachment) {
			$this->transmissionService->handleAttachment($account, $attachment);
		}

		// build mime body
		$headers = [
			'From' => $imapMessage->getFrom()->first()->toHorde(),
			'To' => $imapMessage->getTo()->toHorde(),
			'Cc' => $imapMessage->getCC()->toHorde(),
			'Bcc' => $imapMessage->getBCC()->toHorde(),
			'Subject' => $imapMessage->getSubject(),
			'Date' => Horde_Mime_Headers_Date::create(),
		];

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		if ($message->isHtml()) {
			$mail->setHtmlBody($imapMessage->getContent());
		} else {
			$mail->setBody($imapMessage->getContent());
		}
		$mail->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$perfLogger->step('build local draft message');

		// 'Send' the message
		$client = $this->imapClientFactory->getClient($account);
		try {
			$transport = new Horde_Mail_Transport_Null();
			$mail->send($transport, false, false);
			$perfLogger->step('create IMAP draft message');
			// save the message in the drafts folder
			$draftsMailboxId = $account->getMailAccount()->getDraftsMailboxId();
			if ($draftsMailboxId === null) {
				throw new ClientException('No drafts mailbox configured');
			}
			$draftsMailbox = $this->mailboxMapper->findById($draftsMailboxId);
			$this->messageMapper->save(
				$client,
				$draftsMailbox,
				$mail->getRaw(false),
				[Horde_Imap_Client::FLAG_DRAFT]
			);
			$perfLogger->step('save local draft message on IMAP');
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Drafts mailbox does not exist', 0, $e);
		} catch (Horde_Exception $e) {
			throw new ServiceException('Could not save draft message', 0, $e);
		} finally {
			$client->logout();
		}

		$this->eventDispatcher->dispatchTyped(new DraftSavedEvent($account, null));
		$perfLogger->step('emit post local draft save event');

		$perfLogger->end();
	}

	/**
	 * @param NewMessageData $message
	 * @param Message|null $previousDraft
	 *
	 * @return array
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, ?Message $previousDraft = null): array {
		$perfLogger = $this->performanceLogger->start('save draft');
		$this->eventDispatcher->dispatch(
			SaveDraftEvent::class,
			new SaveDraftEvent($message->getAccount(), $message, $previousDraft)
		);
		$perfLogger->step('emit pre event');

		$account = $message->getAccount();
		$imapMessage = $account->newMessage();
		$imapMessage->setTo($message->getTo());
		$imapMessage->setSubject($message->getSubject());
		$from = new AddressList([
			Address::fromRaw($account->getName(), $account->getEMailAddress()),
		]);
		$imapMessage->setFrom($from);
		$imapMessage->setCC($message->getCc());
		$imapMessage->setBcc($message->getBcc());
		$imapMessage->setContent($message->getBody());

		// build mime body
		$headers = [
			'From' => $imapMessage->getFrom()->first()->toHorde(),
			'To' => $imapMessage->getTo()->toHorde(),
			'Cc' => $imapMessage->getCC()->toHorde(),
			'Bcc' => $imapMessage->getBCC()->toHorde(),
			'Subject' => $imapMessage->getSubject(),
			'Date' => Horde_Mime_Headers_Date::create(),
		];

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		if ($message->isHtml()) {
			$mail->setHtmlBody($imapMessage->getContent());
		} else {
			$mail->setBody($imapMessage->getContent());
		}
		$mail->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$perfLogger->step('build draft message');

		// 'Send' the message
		$client = $this->imapClientFactory->getClient($account);
		try {
			$transport = new Horde_Mail_Transport_Null();
			$mail->send($transport, false, false);
			$perfLogger->step('create IMAP message');
			// save the message in the drafts folder
			$draftsMailboxId = $account->getMailAccount()->getDraftsMailboxId();
			if ($draftsMailboxId === null) {
				throw new ClientException("No drafts mailbox configured");
			}
			$draftsMailbox = $this->mailboxMapper->findById($draftsMailboxId);
			$newUid = $this->messageMapper->save(
				$client,
				$draftsMailbox,
				$mail->getRaw(false),
				[Horde_Imap_Client::FLAG_DRAFT]
			);
			$perfLogger->step('save message on IMAP');
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Drafts mailbox does not exist', 0, $e);
		} catch (Horde_Exception $e) {
			throw new ServiceException('Could not save draft message', 0, $e);
		} finally {
			$client->logout();
		}

		$this->eventDispatcher->dispatch(
			DraftSavedEvent::class,
			new DraftSavedEvent($account, $message, $previousDraft)
		);
		$perfLogger->step('emit post event');

		$perfLogger->end();
		return [$account, $draftsMailbox, $newUid];
	}

	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->flags();
		$query->uid();
		$query->imapDate();
		$query->headerText([
			'cache' => true,
			'peek' => true,
		]);

		$imapClient = $this->imapClientFactory->getClient($account);
		try {
			/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
			$fetchResults = iterator_to_array($imapClient->fetch($mailbox->getName(), $query, [
				'ids' => new Horde_Imap_Client_Ids([$message->getUid()]),
			]), false);
		} finally {
			$imapClient->logout();
		}

		if (count($fetchResults) < 1) {
			throw new ServiceException('Message "' .$message->getId() . '" not found.');
		}

		$imapDate = $fetchResults[0]->getImapDate();
		/** @var Horde_Mime_Headers $headers */
		$mdnHeaders = $fetchResults[0]->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		/** @var Horde_Mime_Headers_Addresses|null $dispositionNotificationTo */
		$dispositionNotificationTo = $mdnHeaders->getHeader('disposition-notification-to');
		/** @var Horde_Mime_Headers_Addresses|null $originalRecipient */
		$originalRecipient = $mdnHeaders->getHeader('original-recipient');

		if ($dispositionNotificationTo === null) {
			throw new ServiceException('Message "' .$message->getId() . '" has no disposition-notification-to header.');
		}

		$headers = new Horde_Mime_Headers();
		$headers->addHeaderOb($dispositionNotificationTo);

		if ($originalRecipient instanceof Horde_Mime_Headers_Addresses) {
			$headers->addHeaderOb($originalRecipient);
		}

		$headers->addHeaderOb(new Horde_Mime_Headers_Subject(null, $message->getSubject()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('From', $message->getFrom()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('To', $message->getTo()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_MessageId(null, $message->getMessageId()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Date(null, $imapDate->format('r')));

		$smtpClient = $this->smtpClientFactory->create($account);

		$mdn = new Horde_Mime_Mdn($headers);
		try {
			$mdn->generate(
				true,
				true,
				'displayed',
				$account->getMailAccount()->getOutboundHost(),
				$smtpClient,
				[
					'from_addr' => $account->getEMailAddress(),
					'charset' => 'UTF-8',
				]
			);
		} catch (Horde_Mime_Exception $e) {
			throw new ServiceException('Unable to send mdn for message "' . $message->getId() . '" caused by: ' . $e->getMessage(), 0, $e);
		}
	}

}

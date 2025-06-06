<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Http;

use JsonSerializable;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse as Base;
use Throwable;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_merge;
use function get_class;

/**
 * @see https://github.com/omniti-labs/jsend
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class JsonResponse extends Base {
	/**
	 * @param Http::STATUS_* $statusCode
	 */
	public function __construct($data = [],
		int $statusCode = Http::STATUS_OK) {
		parent::__construct($data, $statusCode);

		$this->addHeader('x-mail-response', 'true');
	}

	/**
	 * @param array|JsonSerializable|bool|string $data
	 * @param Http::STATUS_* $status
	 *
	 * @return static
	 */
	public static function success($data = null,
		int $status = Http::STATUS_OK): self {
		return new self(
			[
				'status' => 'success',
				'data' => $data,
			],
			$status
		);
	}

	/**
	 * @param array|JsonSerializable|bool|string $data
	 * @param Http::STATUS_* $status
	 *
	 * @return static
	 */
	public static function fail($data = null,
		int $status = Http::STATUS_BAD_REQUEST): self {
		return new self(
			[
				'status' => 'fail',
				'data' => $data,
			],
			$status
		);
	}

	public static function failWith(ClientException $exception): self {
		return self::fail(
			[
				'message' => $exception->getMessage(),
				'type' => get_class($exception),
			],
			$exception->getHttpCode()
		);
	}

	/**
	 * @param string $message
	 * @param Http::STATUS_* $status
	 * @param array|JsonSerializable|bool|string $data
	 *
	 * @return static
	 */
	public static function error(string $message,
		int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
		$data = [],
		int $code = 0): self {
		return new self(
			[
				'status' => 'error',
				'message' => $message,
				'data' => $data,
				'code' => $code,
			],
			$status
		);
	}

	/**
	 * @param Throwable $error
	 * @param Http::STATUS_* $status
	 * @param array|JsonSerializable|bool|string $data
	 *
	 * @return static
	 */
	public static function errorFromThrowable(Throwable $error,
		int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
		$data = []): self {
		if (!is_array($data)) {
			$data = [$data];
		}
		return self::error(
			$error->getMessage(),
			$status,
			array_merge(
				$data,
				self::serializeException($error)
			),
			$error->getCode()
		);
	}

	private static function serializeException(?Throwable $throwable): ?array {
		if ($throwable === null) {
			return null;
		}
		return [
			'type' => get_class($throwable),
			'message' => $throwable->getMessage(),
			'code' => $throwable->getCode(),
			'trace' => self::filterTrace($throwable->getTrace()),
			'previous' => self::serializeException($throwable->getPrevious()),
		];
	}

	private static function filterTrace(array $original): array {
		return array_map(static function (array $row) {
			return array_intersect_key($row,
				array_flip(['file', 'line', 'function', 'class']));
		}, $original);
	}
}

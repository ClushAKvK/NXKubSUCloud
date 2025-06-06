# kitinerary-sys

System executable adapter for the [kitinerary extractor package](https://packagist.org/packages/nextcloud/kitinerary). This package provides an adapter that invokes the binary installed on the system, e.g. with a Linux distribution's package manager.

## Installation

```sh
composer require nextcloud/kitinerary nextcloud/kitinerary-sys
```

## Usage

```php
use Nextcloud\KItinerary\ItineraryExtractor;
use Nextcloud\KItinerary\Sys\SysAdapter;
use Nextcloud\KItinerary\Exception\KItineraryRuntimeException;

$adapter = new SysAdapter();
if (!$adapter->isAvailable()) {
    // ...
}
$extractor = new Extractor($adapter);

try {
    $itinerary = $extractor->extractFromString('...');
} catch (KItineraryRuntimeException $e) {
    // ...
}
```

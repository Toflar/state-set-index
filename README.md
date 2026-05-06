# State Set Index Implementation for PHP

This implements the algorithm presented in the 2012 research paper "Efficient Similarity Search
in Very Large String Sets" by Dandy Fenz, Dustin Lange, Astrid Rheinländer, Felix Naumann,
and Ulf Leser from the Hasso Plattner Institute, Potsdam, Germany and Humboldt-Universität zu Berlin, Department of 
Computer Science, Berlin, Germany.

The algorithm allows to efficiently search through huge datasets with typos (Levenshtein distance) while keeping the
index size small. [Download the paper and read all the details here][Paper].

While this implementation is based on that paper, it is not just a direct integration. The library extends
the approach with additional capabilities such as transposition support and practical helper APIs like
matching-state snapshots that can be used for caching and incremental lookups.

## Installation

Use Composer:

```
composer require toflar/state-set-index
```

## Usage

```php
namespace App;

use Toflar\StateSetIndex\Alphabet\Utf8Alphabet;
use Toflar\StateSetIndex\DataStore\InMemoryDataStore;
use Toflar\StateSetIndex\StateSet\InMemoryStateSet;
use Toflar\StateSetIndex\StateSetIndex;

$stateSetIndex = new StateSetIndex(
    new Config(6, 4),
    new Utf8Alphabet(),
    new InMemoryStateSet(),
    new InMemoryDataStore()
);

$stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann']);
$stateSetIndex->find('Mustre', 2); // Will return ['Muster'];
```

## Configuration

You can configure the maximum index length and maximum alphabet size with the `Config` object. Read the
paper for details on what they do. There's no such thing as a recommended size as it very much depends on what
you want to index and or search.

## Customization

This library ships with the algorithm readily prepared for you to use. The main customization areas will be
the alphabet (both the way it maps characters to labels) and the state set storage, if you want to make the index
persistent. Hence, there are two interfaces that allow you to implement your own logic:

* The `AlphabetInterface` is very straight-forward. It only consists of a `map(string $char, int $alphabetSize)` method 
  which the library needs to map characters to an internal label. Whether you load/store the alphabet in some 
  database is up to you. The library ships with an `InMemoryAlphabet` for reference and simple use cases. You don't 
  even need to store the alphabet as we already have one with the UTF-8 codepoints, that's what `Utf8Alphabet` is 
  for. In case you don't want to customize the labels, use `Utf8Alphabet`.
* The `StateSetInterface` is responsible to load and store information about the state set of your index. Again, 
  how you load/store the state set in some database is up to you. The library ships with an `InMemoryStateSet` 
  for reference and simple use cases and tests.
* The `DataStoreInterface` is responsible for storing the string you index alongside its assigned state. Sometimes 
  you want to completely customize storage in which case you can use the `NullDataStore` and only use the 
  assignments you get as a return value from calling `$stateSetIndex->index()`.

You can not only ask for the final matching results using `$stateSetIndex->findMatchingStates('Mustre', 2)` which is 
already filtered using a multibyte implementation of the Levenshtein algorithm, but you can also access intermediary 
results which you can use to e.g. search your own database for states etc.:

* `$stateSetIndex->findMatchingStates('Mustre', 2)` returns the matching states only.
* `$stateSetIndex->findAcceptedStrings('Mustre', 2)` returns the matching states and the respective accepted strings 
  (unfiltered for false-positives!).
* `$stateSetIndex->find('Mustre', 2)` returns the real matches, filtered for false-positives.

### Snapshots

If your search input grows incrementally (for example while a user types), you can keep and continue
matching-state snapshots instead of recalculating from scratch every time.

```php
$snapshot = $stateSetIndex->createMatchingStatesSnapshot('Muel', 1, 1);

// Later, continue from the existing snapshot as long as the new input still starts with "Muel".
$continued = $stateSetIndex->continueMatchingStatesSnapshot('Mueler', $snapshot);

$states = $continued->matchingStates();
```

If the new input no longer matches the snapshot prefix, continuation falls back to a fresh calculation automatically.

Snapshots are also useful as a cache for repeated or incremental lookups. If you cache them, make sure to
invalidate that cache whenever the indexed state set changes (for example after adding or removing entries),
otherwise you may continue with stale matching states.

[Paper]: https://hpi.de/fileadmin/user_upload/fachgebiete/naumann/publications/PDFs/2012_fenz_efficient.pdf

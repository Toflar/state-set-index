# State Set Index Implementation for PHP

This implements the algorithm presented in the 2012 research paper "Efficient Similarity Search
in Very Large String Sets" by Dandy Fenz, Dustin Lange, Astrid Rheinländer, Felix Naumann,
and Ulf Leser from the Hasso Plattner Institute, Potsdam, Germany and Humboldt-Universität zu Berlin, Department of 
Computer Science, Berlin, Germany.

The algorithm allows to efficiently search through huge datasets with typos (Levenshtein distance) while keeping the
index size small. [Download the paper and read all the details here][Paper].

## Installation

Use Composer:

```
composer require toflar/state-set-index
```

## Usage

```php
namespace App;

use Toflar\StateSetIndex\Alphabet\Utf8Alphabet
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

This library deviates from the research paper in one aspect: it not only supports regular Levenshtein but also 
Damerau-Levenshtein which allows transpositions. So whereas the Levenshtein algorithm would calculate a distance of 
`2` between `Muster` and `Mustre`, Damerau-Levenshtein only calculates `1` as `er` and `re` are swapped/transposed.
You can enable the usage of Damerau-Levenshtein using the third constructor argument of `Config`: `new Config(6, 4, 
true)`.

Note: TODO.

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

[Paper]: https://hpi.de/fileadmin/user_upload/fachgebiete/naumann/publications/PDFs/2012_fenz_efficient.pdf
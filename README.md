# Html

![license](https://img.shields.io/badge/license-MIT-brightGreen.svg)
[![build](https://github.com/originphp/html/workflows/CI/badge.svg)](https://github.com/originphp/html/actions)
[![coverage](https://coveralls.io/repos/github/originphp/html/badge.svg?branch=master)](https://coveralls.io/github/originphp/html?branch=master)

The Html class provides some useful functions for when working with HTML.

## Installation

To install this package

```linux
$ composer require originphp/html
```

Then add to your file

```
use Origin\Html\Html;
```

## From Text

If you need to convert a text block to html

```php
$text = <<< EOF
This is a paragrpah.
This is another line part of the paragraph.

This is a new paragraph.
EOF;
$html = Html::fromText($text);
```

This will create:

```html
<p>This is a paragrpah.<br>This is another line part of the paragraph.</p>
<p>This is a new paragraph.</p>
```

If want paragraphs to be wrapped with a different tag than `p` then would do so like this

```php
$html = Html::fromText($text,['tag'=>'div']);
```

## To Text

You can also convert a HTML string to formatted text

```php
$text = Html::toText($html);
```

For example

```php
$html = <<< EOF
<h1>Search Engines</h1>
<h2>Google</h2><h3>About</h3>
<blockquote>Google is not a conventional company. We do not intend to become one.</blockquote>
<p>Google LLC is an American        multinational technology 
company that specializes in Internet-related services and products, which include online advertising technologies, search engine, cloud computing, software, and hardware.<br>It is considered one of the Big Four technology companies, alongside Amazon, Apple and Facebook.</p>
<p>Benefits of using Google:</p>
<ol>
    <li>Good quality search results</li>
    <li>Relevent advertising</li>
</ol>
<p>Important links:</p>
<ul>
    <li><a href="https://en.wikipedia.org/wiki/Google">Google's Wikipedia Page</a></li>
    <li><a href="https://abc.xyz/">Alphabet</a></li>
</ul>
<h3>Financial Results</h3>
<p>Below are the <span>financial</span> results for the last <em>3 years</em>.</p>
<table>
<tr>
        <th>Revenue</th>
        <th>31/12/2018</th>
        <th>31/12/2017</th>
        <th>31/12/2016</th>
</tr>
<tr>
        <td>Total revenue</td>
        <td>136,819,000</td>
        <td>110,855,000</td>
        <td>90,272,000</td>
</tr>
<tr>
        <td>Cost of revenue</td>
        <td>59,549,000</td>
        <td>45,583,000</td>
        <td>35,138,000</td>
</tr>
<tr>
        <td>Gross profit</td>
        <td><strong>77,270,000</strong></td>
        <td><strong>65,272,000</strong></td>
        <td><strong>55,134,000</strong></td>
</tr>
</table>
<h3>Using Google API</h3>
<p>You can use the <a href="https://github.com/googleapis/google-api-php-client/tree/master/examples">Google API</a> to access various Google services.</p>
<p>To install the library:</p>
<pre>
<code>composer require google/apiclient:^2.0</code>
</pre>
<p>Create a file called <code>quickstart.php</code> and add the following contents</p>
<pre><code>require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
... truncated
</code></pre>
EOF;
```

Will output

```text
Search Engines
==============

Google
------

About
-----

"Google is not a conventional company. We do not intend to become one."

Google LLC is an American multinational technology company that specializes in Internet-related services and products, which include online advertising technologies, search engine, cloud computing, software, and hardware.
It is considered one of the Big Four technology companies, alongside Amazon, Apple and Facebook.

Benefits of using Google:

1. Good quality search results
2. Relevent advertising

Important links:

- Google's Wikipedia Page [https://en.wikipedia.org/wiki/Google]
- Alphabet [https://abc.xyz/]

Financial Results
-----------------

Below are the financial results for the last 3 years.

+------------------+--------------+--------------+-------------+
| Revenue          | 31/12/2018   | 31/12/2017   | 31/12/2016  |
+------------------+--------------+--------------+-------------+
| Total revenue    | 136,819,000  | 110,855,000  | 90,272,000  |
| Cost of revenue  | 59,549,000   | 45,583,000   | 35,138,000  |
| Gross profit     | 77,270,000   | 65,272,000   | 55,134,000  |
+------------------+--------------+--------------+-------------+

Using Google API
----------------

You can use the [Google API](https://github.com/googleapis/google-api-php-client/tree/master/examples) to access various Google services.

To install the library:

composer require google/apiclient:^2.0

Create a file called quickstart.php and add the following contents

     require __DIR__ . '/vendor/autoload.php';
     
     if (php_sapi_name() != 'cli') {
         throw new Exception('This application must be run on the command line.');
     }
     ... truncated
     
```

To create text version without formatting:

```php
$text = Html::toText($html,['format'=>false]);
```

The main difference is headings, tables, code etc are not formatted. The HTML is cleaned up, line breaks are added, and lists are converted. If a list has a sublist then indentation will be added.

## Minify

Minify cleans up the spacing, removes comments and thus minifies a HTML string.

```php
$minified = Html::minify($html);
```

The following options are supported

- collapseWhitespace: default:true. Collapse whitespace in the text nodes
- conservativeCollapse: default:false. Always collapse whitespace to at least 1 space
- collapseInlineTagWhitespace: default:false. Don't leave any spaces between inline elements.
- minifyJs: default:false minifies inline Javascript (beta)
- minifyCss: default:false minifies inline CSS (beta)

## Sanitize

Sanitize enables to only allow certain tags and attributes in a HTML string.

```php
$html = Html::sanitize($html,[
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'p',
    'i', 'em', 'strong', 'b', 'blockquote', 'del',
    'a' => ['href'],
    'ul', 'li', 'ol', 'br',
    'code', 'pre',
    'img' => ['src','alt']]
    );
```


## Strip Tags

To strip selected tags and their content from a HTML string, in other words strip tags that are in a blacklist

```php
$html = Html::stripTags($html,['script','iframe','img']);
```

## Escape

It is important when displaying user inputted HTML that it is escaped properly for security reasons, see [Cross-site scripting](https://www.google.com/about/appsecurity/learning/xss/) for more information.

```php
$escaped = Html::escape($html);
```
A wrapper for the TCPDF class that makes it easier to use it for building PDF
tables.

## Requirements
TCPDF must already be loaded.


## Install

```sh
composer require phillipsdata/tcpdf_wrapper
```

## Basic Usage

### Draw a table

```php
// Instantiate an instance of the TcpdfWrapper
$oprientation = 'P';
$unit = 'mm';
$format = 'A4';
$unicade = true;
$encoding = 'UTF-8';
$diskcache = false;

$wrapper = new TcpdfWrapper($orientation, $unit, $format, $unicode, $encoding, $diskcache);

// Create a numerically-indexed array of row and column data
// where the key is the column name and the value is the value to display
$data = array(
    array(
        'name' => 'John Doe',
        'fav_color' => 'Blue'
    ),
    array(
        'name' => 'Jane Doe',
        'fav_color' => 'Red'
    )
);

// Define options for the table and the data columns
$options = array(
    'x_pos' => 44,
    'y_pos' => 246,
    'border' => 'RL',
    'height' => 16,
    'line_style' => array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter'),
    'font_size' => 12,
    'col' => array(
        'name' => array(
            'width' => 200,
            'align' => 'L'
        ),
        'fav_color' => array(
            'width' => 100,
            'align' => 'C'
        )
    )
);

$wrapper->drawTable($data, $options); // Draws the given data into the PDF
```

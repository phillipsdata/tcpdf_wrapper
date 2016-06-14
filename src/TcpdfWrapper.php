<?php
namespace PhillipsData\TcpdfWrapper;

use TCPDF;

/**
 * TCPDF Wrapper. Extends the TCPDF library to make it easier to use for
 * building PDFs.
 *
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 */
class TcpdfWrapper extends TCPDF
{

    /**
     * Draws a table using the given data and options
     *
     * @param array $data An array of 'column' => values
     * @param array $options An array of options affecting the table including:
     *  - type The type of table (multicell or cell, default 'multicell')
     *  - x_pos The X position of the table (default current X post)
     *  - y_pos The Y position of the table (default current Y pos)
     *  - border Border thickness (default 0)
     *  - align Table alignment (default L)
     *  - text_color An RGB array of text color (default null, whatever the default text color is set to)
     *  - font_size The font size for the table (default current font size)
     *  - height The width of the cell(s) (default 0 - auto)
     *  - width The height of the cell(s) (default 0 - to end of screen)
     *  - font The font to set for the cell(s)
     *  - line_style The line style attributes (@see TCPDF::setLineStyle())
     *  - fill_color The color to fill the cell(s) with
     *  - padding The padding value to use for the cell(s) (null - auto padding)
     *  - is_html True or false, whether the table contains HTML
     *  - col All options from $options that affect the given column by name or index
     *  - row All options from $options that affect the given row by index
     *  - cell All options from $options that affect the given cell by both column and row
     */
    public function drawTable(array $data = array(), $options = null)
    {
        $opt = array(
            'type' => 'multicell', // Accepted types: multicell, cell
            'x_pos' => $this->GetX(),
            'y_pos' => $this->GetY(),
            'border' => 0,
            'align' => 'L',
            'width' => 0,
            'height' => 0,
            'text_color' => null,
            'font_size' => $this->getFontSize(),
            'is_html' => false
        );

        // Overwrite default options
        $opt = array_merge($opt, (array) $options);

        // Fetch the original font-family (to be restored)
        $this->orig_font = $this->getFontFamily();

        // Fetch the original font-size (to be restored)
        $this->orig_font_size = $this->getFontSize();

        // Fetch the original font-family (to be restored)
        $this->orig_font_style = $this->getFontStyle();

        // Set the location of this table
        $this->SetXY($opt['x_pos'], $opt['y_pos']);

        // Insert rows
        for ($i = 0; $i < count($data); $i++) {
            // Attempt to draw the row so we can fetch the height
            $clone = clone $this;
            $max_units = $clone->drawRow($data[$i], $i, $opt);
            // Disgard what we've drawn
            unset($clone);

            // Now draw the row again, this time with all cells
            // the same height
            $prev_opt_height = isset($opt['height']) ? $opt['height'] : null;
            $opt['height'] = $max_units;

            $this->drawRow($data[$i], $i, $opt);

            if ($prev_opt_height !== null) {
                $opt['height'] = $prev_opt_height;
            } else {
                $opt['height'] = 0;
            }
        }
    }

    /**
     * Renders the table row
     *
     * @param array $row The row to render
     * @param int $i The index of this row in the table
     * @param array $opt An array of render options
     * @return int The maximum number of units required to render the height of the tallest cell
     */
    private function drawRow($row, $i, $opt)
    {

        $max_units = 0;

        // Set X position of this row
        $this->SetX($opt['x_pos']);

        if (is_array($row)) {
            $j = count($row);
            $end_row = false;

            $page_start = $this->getPage();
            $y_start = $this->GetY();
            $y_end = array();
            $page_end = array();

            // Render each column of the given row
            foreach ($row as $col => $text) {
                $lines = 0;

                // Set the cell options by merging options at various levels (table + col + row + cell)
                $cell_options = array_merge(
                    $opt,
                    (array) (isset($opt['col'][$col]) ? $opt['col'][$col] : null),
                    (array) (isset($opt['row'][$i]) ? $opt['row'][$i] : null),
                    (array) (isset($opt['cell'][$i][$col]) ? $opt['cell'][$i][$col] : null)
                );

                if (--$j == 0) {
                    $end_row = true;
                }

                // Set the cell padding if given
                $orig_cell_padding = $this->cMargin;
                if (key_exists('padding', $cell_options) && $cell_options['padding'] !== null) {
                    $this->SetCellPadding($cell_options['padding']);
                }

                // Determine line style for this cell
                if (isset($cell_options['line_style']) && is_array($cell_options['line_style'])) {
                    $this->SetLineStyle($cell_options['line_style']);
                }

                $fill = 0; // transparent by default (0)
                // Set fill color, if available
                if (isset($cell_options['fill_color']) && is_array($cell_options['fill_color'])) {
                    $this->SetFillColorArray($cell_options['fill_color']);
                    $fill = 1; // color given, so fill it (1 non-transparent)
                }

                // Set text color, if available
                $this->prev_text_color = $this->fgcolor;

                if (isset($cell_options['text_color']) && is_array($cell_options['text_color'])) {
                    $this->SetTextColorArray($cell_options['text_color']);
                }

                // Set font size specified
                if (isset($cell_options['font_size']) && is_numeric($cell_options['font_size'])) {
                    $this->SetFontSize($cell_options['font_size']);
                }

                // Determine the font for this cell
                if (isset($cell_options['font'])) {
                    if (is_array($cell_options['font'])) {
                        call_user_func_array(array(&$this, "SetFont"), $cell_options['font']);
                    } else {
                        $this->SetFont(
                            $cell_options['font'],
                            isset($cell_options['font_style'])
                            ? $cell_options['font_style']
                            : ''
                        );
                    }
                }

                $w = max($opt['width'], $cell_options['width']);
                $h = max($opt['height'], $cell_options['height']);
                $border = (isset($cell_options['border']) ? $cell_options['border'] : null);

                $text_align = (isset($cell_options['align']) ? $cell_options['align'] : null);

                // Set page to begin drawing cell on
                $this->setPage($page_start);

                if ($cell_options['type'] == 'cell') {
                    $this->Cell($w, $h, $text, $border, ($end_row ? 1 : 0), $text_align, $fill);
                } else {
                    if ($end_row) {
                        $lines = $this->MultiCell(
                            $w,
                            $h,
                            $text,
                            $border,
                            $text_align,
                            $fill,
                            1,
                            $this->GetX(),
                            $y_start,
                            true,
                            0,
                            (array_key_exists('is_html', $cell_options) ? (bool)$cell_options['is_html'] : false)
                        );
                    } else {
                        $lines = $this->MultiCell(
                            $w,
                            $h,
                            $text,
                            $border,
                            $text_align,
                            $fill,
                            2,
                            $this->GetX(),
                            $y_start,
                            true,
                            0,
                            (array_key_exists('is_html', $cell_options) ? (bool)$cell_options['is_html'] : false)
                        );
                    }
                }

                // Calculate the height of the cell that was just drawn
                $max_units = max($max_units, $this->getLastH());

                // Record page and y position cell finished drawing on
                $page_end[] = $this->getPage();
                $y_end[] = $this->GetY();

                // Rest the font
                $this->SetFont($this->orig_font, $this->orig_font_style, $this->orig_font_size);
                // Reset the font color
                $this->SetTextColorArray($this->prev_text_color);
                // Reset cell padding
                $this->SetCellPadding($orig_cell_padding);
            }

            $y_new = $y_end[count($y_end) - 1];

            // set the new row position by case
            if (max($page_end) == $page_start) {
                $y_new = max($y_end);
            } elseif ($page_end[0] == $page_end[count($page_end) - 1]) {
                $y_new = max($y_end);
            } elseif ($page_end[0] > $page_end[count($page_end) - 1]) {
                $y_new = $y_end[0];
            }

            $this->setPage(max($page_end));

            $this->SetY($y_new);
        }

        return $max_units;
    }

    /**
     * Terminates the current page if not already terminated
     */
    public function endPage()
    {
        parent::endPage();
    }
}

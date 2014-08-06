<?php

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

use ArrayIterator;
use Behat\Gherkin\Exception\NodeException;
use Iterator;
use IteratorAggregate;

/**
 * Represents Gherkin Table argument.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TableNode implements ArgumentInterface, IteratorAggregate
{
    /**
     * @var array
     */
    private $table;
    /**
     * @var integer
     */
    private $maxLineLength = array();

    /**
     * Initializes table.
     *
     * @param array $table Table in form of [$rowLineNumber => [$val1, $val2, $val3]]
     */
    public function __construct(array $table)
    {
        $this->table = $table;

        foreach ($this->getRows() as $row) {
            foreach ($row as $column => $string) {
                if (!isset($this->maxLineLength[$column])) {
                    $this->maxLineLength[$column] = 0;
                }

                $this->maxLineLength[$column] = max($this->maxLineLength[$column], mb_strlen($string, 'utf8'));
            }
        }
    }

    /**
     * Returns node type.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Table';
    }

    /**
     * Returns table hash, formed by columns (ColumnsHash).
     *
     * @return array
     */
    public function getHash()
    {
        return $this->getColumnsHash();
    }

    /**
     * Returns table hash, formed by columns.
     *
     * @throws NodeException
     * @return array
     */
    public function getColumnsHash()
    {
        $rows = $this->getRows();
        $keys = array_shift($rows);

        $hash = array();
        foreach ($rows as $row) {
            $hash[] = array_combine($keys, $row);
        }

        if (empty($hash)) {
            throw new NodeException("Could not get columns hash. It's likely the table is malformed (missing headers)");
        }

        return $hash;
    }

    /**
     * Returns table hash, formed by rows.
     *
     * @return array
     */
    public function getRowsHash()
    {
        $hash = array();

        foreach ($this->getRows() as $row) {
            $hash[array_shift($row)] = (1 == count($row)) ? $row[0] : $row;
        }

        return $hash;
    }

    /**
     * Returns numerated table lines.
     * Line numbers are keys, lines are values.
     *
     * @return array
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns table rows.
     *
     * @return array
     */
    public function getRows()
    {
        return array_values($this->table);
    }

    /**
     * Returns table definition lines.
     *
     * @return array
     */
    public function getLines()
    {
        return array_keys($this->table);
    }

    /**
     * Returns specific row in a table.
     *
     * @param integer $index Row number
     *
     * @return array
     *
     * @throws NodeException If row with specified index does not exist
     */
    public function getRow($index)
    {
        $rows = $this->getRows();

        if (!isset($rows[$index])) {
            throw new NodeException(sprintf('Rows #%d does not exist in table.', $index));
        }

        return $rows[$index];
    }

    /**
     * Returns line number at which specific row was defined.
     *
     * @param integer $index
     *
     * @return integer
     *
     * @throws NodeException If row with specified index does not exist
     */
    public function getRowLine($index)
    {
        $lines = array_keys($this->table);

        if (!isset($lines[$index])) {
            throw new NodeException(sprintf('Rows #%d does not exist in table.', $index));
        }

        return $lines[$index];
    }

    /**
     * Converts row into delimited string.
     *
     * @param integer $rowNum Row number
     *
     * @return string
     */
    public function getRowAsString($rowNum)
    {
        $values = array();
        foreach ($this->getRow($rowNum) as $column => $value) {
            $values[] = $this->padRight(' ' . $value . ' ', $this->maxLineLength[$column] + 2);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    /**
     * Converts row into delimited string.
     *
     * @param integer  $rowNum  Row number
     * @param callable $wrapper Wrapper function
     *
     * @return string
     */
    public function getRowAsStringWithWrappedValues($rowNum, $wrapper)
    {
        $values = array();
        foreach ($this->getRow($rowNum) as $column => $value) {
            $value = $this->padRight(' ' . $value . ' ', $this->maxLineLength[$column] + 2);

            $values[] = call_user_func($wrapper, $value, $column);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    /**
     * Converts entire table into string
     *
     * @return string
     */
    public function getTableAsString()
    {
        $lines = array();
        for ($i = 0; $i < count($this->getRows()); $i++) {
            $lines[] = $this->getRowAsString($i);
        }

        return implode("\n", $lines);
    }

    /**
     * Returns line number at which table was started.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->getRowLine(0);
    }

    /**
     * Converts table into string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTableAsString();
    }

    /**
     * Retrieves a hash iterator.
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getHash());
    }

    /**
     * Pads string right.
     *
     * @param string  $text   Text to pad
     * @param integer $length Length
     *
     * @return string
     */
    protected function padRight($text, $length)
    {
        while ($length > mb_strlen($text, 'utf8')) {
            $text = $text . ' ';
        }

        return $text;
    }
}

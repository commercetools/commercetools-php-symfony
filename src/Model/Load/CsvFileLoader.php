<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/11/16
 * Time: 11:28
 */

namespace Commercetools\Symfony\CtpBundle\Model\Load;

use Commercetools\Symfony\CtpBundle\Model\Transform\CsvToJson;

class CsvFileLoader
{
    private $csvToJson;
    private $delimiter = ';';
    private $enclosure = '"';
    private $escape = '\\';

    public function __construct(CsvToJson $csvToJson)
    {
        $this->csvToJson = $csvToJson;
    }

    public function load($fileName)
    {
        try {
            $file = new \SplFileObject($fileName, 'rb');
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException(sprintf('Error opening file "%s".', $fileName), 0, $e);
        }

        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

        $headings = null;
        foreach ($file as $line) {
            if (empty($line)) {
                continue;
            }
            if (is_null($headings)) {
                $headings = array_flip($line);
                continue;
            }

            yield $this->csvToJson->transform($line, $headings);
        }
    }

    /**
     * Sets the delimiter, enclosure, and escape character for CSV.
     *
     * @param string $delimiter delimiter character
     * @param string $enclosure enclosure character
     * @param string $escape    escape character
     */
    public function setCsvControl($delimiter = ';', $enclosure = '"', $escape = '\\')
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }
}

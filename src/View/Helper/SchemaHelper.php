<?php
namespace Schema\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Riimu\Kit\PHPEncoder\PHPEncoder;

/**
 * Schema helper
 */
class SchemaHelper extends Helper
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Generates the PHP array string for an array of records. Will use
     * var_export() and PHPEncoder for more sophisticated types.
     *
     * @param array $records Array of seed records
     * @return string PHP Code
     */
    public function stringifyRecords(array $records)
    {
        $out = "[\n";
        $out = '';
        $encoder = new PHPEncoder();

        foreach ($records as $record) {
            $values = [];
            foreach ($record as $field => $value) {
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                if (is_array($value)) {
                    // FIXME: the encoder will forget precisions of floats
                    $val = $encoder->encode($value, [
                        'array.inline' => false,
                        'array.omit' => false,
                        'array.indent' => 4,
                        'boolean.capitalize' => false,
                        'null.capitalize' => false,
                        'string.escape' => false,
                        'array.base' => 12,
                        'float.integers' => "all",
                        'float.precision' => false
                    ]);
                } else {
                    $val = var_export($value, true);
                }

                if ($val === 'NULL') {
                    $val = 'null';
                }
                $values[] = "            '$field' => $val";
            }
            $out .= "        [\n";
            $out .= implode(",\n", $values);
            $out .= "\n        ],\n";
        }
        #$out .= "    ]";
        return $out;
    }
}

<?php
namespace TableImporter;

/** 
 * class TableImporterTemplate
 * Super basic templating engine
 */
class TableImporterTemplate
{
    protected $_file;
    protected $_data = array();

    public function __construct($file = null)
    {
        $this->_file = $file;
    }

    /**
     * Set template variable values
     * @param string $key Variable name
     * @param string $value Variable value
     * @return null
     */
    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    /**
     * Replace variables in the template and return a populated file
     * @return string
     */
    public function render()
    {
        extract($this->_data);
        ob_start();
        include($this->_file);
        return ob_get_clean();
    }
}

?>
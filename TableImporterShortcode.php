<?php

namespace Grav\Plugin\Shortcodes;

use DOMDocument;
use DOMElement;
use Exception;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Grav\Common\Utils;
use Symfony\Component\Yaml\Yaml;
use League\Csv\Reader;
use League\Csv\Statement;
use Grav\Common\Grav;

class TableImporterShortcode extends Shortcode
{
    protected $outerEscape = null;
    protected $defaults = [];
    protected const ERROR_DIV = '<div class="notices red">';
    protected const ERROR_CLOSE = '</div>';

    /**
     * Initializes the shortcode handler.
     */
    public function init()
    {
        $this->shortcode->getHandlers()->add('ti', [$this, 'process']);
        $this->defaults = (array) $this->config->get('plugins.table-importer.default');
    }

    /**
     * Main shortcode processing method.
     *
     * @param ShortcodeInterface $sc The shortcode object.
     * @return string The generated HTML table or an error message.
     */
    public function process(ShortcodeInterface $sc): string
    {
        // 1. Extract and Validate File Path
        $fn = $this->getFileName($sc);
        if (empty($fn)) {
            return $this->errorMsg(
                "Malformed shortcode",
                "Table Importer: Malformed shortcode (<tt>%s</tt>).",
                htmlspecialchars($sc->getShortcodeText())
            );
        }

        // 2. Extract and Validate Parameters
        $params = $this->extractAndValidateParams($sc);
        if (is_string($params)) {
            return $params; // Returns the error message string
        }
        extract($params); // Imports $type, $delim, $encl, $esc, $class, $id, $caption, $raw, $header, $footer

        // 3. Resolve and Validate File Existence
        $abspath = $this->resolvePath($fn);
        if ($abspath === null) {
            return $this->errorMsg("Could not resolve file name", "Could not resolve file name '%s'.", $fn);
        }
        if (!file_exists($abspath)) {
            return $this->errorMsg("Could not find data file", "Could not find the requested data file '%s'.", $fn);
        }

        // 4. Load Data from File
        try {
            $data = $this->loadData($abspath, $fn, $type, $delim, $encl, $esc);
        } catch (\Exception $e) {
            Grav::instance()['debugger']->addMessage($e->getMessage());
            return $this->errorMsg(
                "Malformed data",
                "The data in '%s' appears to be malformed. Please review the documentation.",
                $fn
            );
        }

        // 5. Generate HTML Table
        return $this->generateTableHtml($data, $header, $footer, $params);
    }

    /**
     * Extracts the file name from the shortcode.
     * @param ShortcodeInterface $sc
     * @return string|null
     */
    protected function getFileName(ShortcodeInterface $sc): ?string
    {
        $fn = $sc->getParameter('file', null);
        if ($fn === null) {
            // Process the file if no "file" param is set (legacy format: [ti=filename.csv/])
            $fn = $sc->getShortcodeText();
            $fn = str_replace('[ti=', '', $fn);
            $fn = str_replace('/]', '', $fn);
            $fn = trim($fn);
        }
        return $fn === '' ? null : $fn;
    }

    /**
     * Extracts and validates all parameters from the shortcode.
     * @param ShortcodeInterface $sc
     * @return array|string Array of parameters or an error string.
     */
    protected function extractAndValidateParams(ShortcodeInterface $sc): array|string
    {
        $type = $sc->getParameter('type', $this->defaults['type'] ?? null);
        $delim = $sc->getParameter('delimiter', $this->defaults['csv']['delimiter'] ?? ',');
        $encl = $sc->getParameter('enclosure', $this->defaults['csv']['enclosure'] ?? '"');
        $esc = $sc->getParameter('escape', $this->defaults['csv']['escape'] ?? '\\');
        $class = $sc->getParameter('class', $this->defaults['class'] ?? null);
        $id = $sc->getParameter('id', $this->defaults['id'] ?? null);
        $caption = $sc->getParameter('caption', $this->defaults['caption'] ?? null);

        $raw = filter_var($sc->getParameter('raw', $this->defaults['raw'] ?? null), FILTER_VALIDATE_BOOLEAN);
        $header = filter_var($sc->getParameter('header', $this->defaults['header'] ?? null), FILTER_VALIDATE_BOOLEAN);
        $footer = filter_var($sc->getParameter('footer', $this->defaults['footer'] ?? null), FILTER_VALIDATE_BOOLEAN);

        // Validation logic moved here for better separation
        if (strlen($delim) > 1) {
            return $this->errorMsg("Delimiter error", "delimiter should be a single char! '%s' given.", $delim);
        }
        if ($encl !== null && strlen($encl) > 1) {
            return $this->errorMsg("Enclosure error", "Enclosure should be a single char or none! '%s' given.", $encl);
        }
        if ($esc !== null && strlen($esc) > 1) {
            return $this->errorMsg("Escape error", "Escape should be a single char or none! '%s' given.", $esc);
        }

        return compact('type', 'delim', 'encl', 'esc', 'class', 'id', 'caption', 'raw', 'header', 'footer');
    }

    /**
     * Resolves the absolute path of the file.
     * @param string $fn The file name/path from the shortcode.
     * @return string|null The absolute path or null if not found.
     */
    protected function resolvePath(string $fn): ?string
    {
        $fn = static::sanitize($fn);
        $path = $this->grav['shortcode']->getPage()->path();

        // Check for 'data:' prefix to look in user://data
        if (Utils::startswith($fn, 'data:')) {
            $path = $this->grav['locator']->findResource('user://data', true);
            $fn = str_replace('data:', '', $fn);
        }

        // Use DS (DIRECTORY_SEPARATOR) correctly for cross-platform compatibility
        $fullPath = rtrim($path, DS) . DS . ltrim($fn, DS);

        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Loads data from the specified file path based on type.
     *
     * @param string $abspath Absolute file path.
     * @param string $fn Original file name for error reporting.
     * @param string|null $type File type hint.
     * @param string $delim CSV delimiter.
     * @param string $encl CSV enclosure.
     * @param string $esc CSV escape character.
     * @return array Loaded data array.
     * @throws Exception If file type is unsupported or data loading fails.
     */
    protected function loadData(string $abspath, string $fn, ?string &$type, string $delim, string $encl, string $esc): array
    {
        $data = null;
        if ($type === null) {
            $type = pathinfo($fn, PATHINFO_EXTENSION);
        }

        // Enforce lowercase type for switch-case
        $type = strtolower($type);

        switch ($type) {
            case 'yml':
            case 'yaml':
                $data = Yaml::parse(file_get_contents($abspath));
                break;

            case 'json':
                // Setting true for associative array conversion is typical for table data.
                $data = json_decode(file_get_contents($abspath), true);
                break;

            case 'csv':
                $reader = Reader::createFromPath($abspath, 'r');
                $reader->setDelimiter($delim);
                $reader->setEnclosure($encl);
                $this->outerEscape = $esc;
                $reader->setEscape($esc);

                $resultSet = Statement::create()->process($reader);
                // Convert to array. `true` ensures keys are preserved (though typically sequential for CSV)
                $data = iterator_to_array($resultSet, true);
                break;

            default:
                throw new Exception(sprintf(
                    "Table Importer: Could not determine the type of the requested data file '%s'. This plugin only supports YAML, JSON, and CSV.",
                    $fn
                ));
        }

        if ($data === null || !is_array($data)) {
            throw new Exception(sprintf(
                "Table Importer: Something went wrong loading '%s' data from the requested file '%s'.",
                $type,
                $fn
            ));
        }

        return $data;
    }

    /**
     * Generates the final HTML table string.
     * @param array $data The table data (rows).
     * @param bool $header Flag for header row.
     * @param bool $footer Flag for footer row.
     * @param array $params All shortcode parameters.
     * @return string The HTML table.
     */
    protected function generateTableHtml(array $data, bool $header, bool $footer, array $params): string
    {
       // Extract header/footer data before iterating over $data
        if ($header) {
            $headerData = array_shift($data);
        }
        if ($footer) {
            $footerData = array_pop($data);
        }

        $doc = new DOMDocument('1.0');
        $table = $doc->createElement('table');

        // Set optional attributes
        if (!empty($params['id'])) {
            $table->setAttribute('id', $params['id']);
        }
        if (!empty($params['class'])) {
            $table->setAttribute('class', htmlspecialchars($params['class']));
        }
        if (!empty($params['caption'])) {
            $table->appendChild(
                $doc->createElement('caption', htmlspecialchars($params['caption']))
            );
        }

        // 1. Generate Thead
        if ($header && is_array($headerData)) {
            $table->appendChild(
                $this->createNested($doc, $headerData, 'thead', 'tr', 'th')
            );
        }

        // 2. Generate Tbody
        $tbody = $table->appendChild($doc->createElement('tbody'));
        
        // Find the maximum column count to determine the total width of the table.
        // This is necessary if we want to pad shorter rows with empty cells.
        // If $header is true, the max columns should be based on the header count.
        $maxCols = $header ? count($headerData) : 0;
        
        // If no header, iterate through data to find the max column count among all rows.
        if (!$header) {
            foreach ($data as $row) {
                $maxCols = max($maxCols, count($row));
            }
        }
        
        foreach ($data as $row) {
            // Ensure $row is an array before processing
            if (!is_array($row)) {
                // Optionally log a debug message or skip non-array rows
                continue;
            }

            $tr = $tbody->appendChild($doc->createElement("tr"));
            $currentCols = 0;

            // Process existing cells in the row
            foreach ($row as $cell) {
                // Cast cell to string if it's not already
                $cell = (string) $cell;
                
                if ($params['raw']) {
                    $td = $tr->appendChild($doc->createElement("td"));
                    $td->appendChild($doc->createCDATASection($cell));
                } else {
                    $tr->appendChild($doc->createElement("td", htmlspecialchars($cell)));
                }
                $currentCols++;
            }

            // Pad the row with empty cells if it's shorter than the maximum width
            if ($maxCols > 0 && $currentCols < $maxCols) {
                $missingCols = $maxCols - $currentCols;
                for ($i = 0; $i < $missingCols; $i++) {
                    $tr->appendChild($doc->createElement("td"));
                }
            }
        }

        // 3. Generate Tfoot
        if ($footer && is_array($footerData)) {
            $table->appendChild(
                $this->createNested($doc, $footerData, 'tfoot', 'tr', 'td')
            );
        }

        $doc->formatOutput = true;
        $doc->appendChild($table);

        $content = $doc->saveHTML();
        $tableStart = strpos($content, '<table>');
        $tableEnd = strpos($content, '</table>');
        if ($tableStart !== false && $tableEnd !== false) {
            $content = substr($content, $tableStart, $tableEnd - $tableStart + 8);
        }

        return $content;
    }

    /**
     * Helper to create a standardized error message wrapper.
     * @param string $heading
     * @param string $messageSprintf
     * @param mixed ...$args
     * @return string
     */
    protected function errorMsg(string $heading, string $messageSprintf, ...$args): string
    {
        return self::ERROR_DIV . "<h3>Table Importer: " . htmlspecialchars($heading) . "</h3><p>" .
            vsprintf($messageSprintf, $args) . "</p>" . self::ERROR_CLOSE;
    }

    /**
     * Sanitizes the filename by removing path traversal attempts.
     * @param string $fn
     * @return string
     */
    private static function sanitize(string $fn): string
    {
        $fn = trim($fn);
        $fn = str_replace('..', '', $fn); // Prevent path traversal
        $fn = ltrim($fn, DS); // Remove leading directory separator
        $fn = str_replace(DS . DS, DS, $fn); // Reduce double separators
        return $fn;
    }

    /**
     * Creates a nested DOM structure (e.g., <thead><tr><th>...</th></tr></thead>).
     *
     * @param DOMDocument $dom The document object.
     * @param array $data The cell data.
     * @param string $pNode The parent node (e.g., 'thead', 'tfoot').
     * @param string $rNode The row node ('tr').
     * @param string $cNode The cell node ('th', 'td').
     * @return DOMElement
     */
    private function createNested(DOMDocument $dom, array $data, string $pNode, string $rNode, string $cNode): DOMElement
    {
        $retElement = $dom->createElement($pNode);
        $rowNode = $retElement->appendChild($dom->createElement($rNode));

        foreach ($data as $cell) {
            // Use htmlspecialchars to protect data in standard cells, assuming $cNode is 'th' or 'td'.
            // Note: If raw data is ever passed here, this protection might need an override.
            $rowNode->appendChild($dom->createElement($cNode, htmlspecialchars((string) $cell)));
        }

        return $retElement;
    }
}
<?php
namespace TableImporter;

class TableImporterStrings
{
    public static function StickyHeaderCss(string $tableId): string
    {
        return<<<'EOT'
            table#$tableId {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* Adjust margin as needed */
            }

            table#$tableId thead, 
            table#$tableId tbody {
            display: block;
            }

            table#$tableId thead {
            position: sticky;
            top: 0;
            }

            table#$tableId th,
            table#$tableId td {
            padding: 10px;
            text-align: left;
            }

            table#$tableId tbody {
            max-height: 300px;
            overflow-y: auto;
            }
EOT;
    }

}

?>
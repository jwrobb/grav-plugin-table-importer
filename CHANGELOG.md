# v3.1.0
## 06/21/2025

1. [](#bugfix)
    * Fixed issue with when type was set in the shortcode
1. [](#improved)
    * Now, except for the file path indeed, a site wide default value can be set for all params. (fixes breaking change where the header was previously set to true)

# v3.0.1
## 12/30/2024

1. [](#bugfix)
    * Fixed issue with raw cell data being escaped
    * Fixed issue with captions param not enabling table caption rendering

# v3.0.0
## 09/07/2024

1. [](#new)
    * Added table footer param and logic
1. [](#improved)
    * Rewrote table HTML output logic to use DOMElements rather than string concatenation
    * Cleaned up param parsing code

# v2.3.0
## 04/23/2024

1. [](#new)
    * Updated League CSV to version 9.8 (drops support for PHP < v7.4) 
    * Updated CSV logic for new lib methods
    * Updated Readme

# v2.2.1
## 11/12/2019

1. [](#bugfix)
    * Fixed a bug involving a blank `page` variable (thanks to @gamahachaa).

# v2.2.0
## 07/16/2018

1. [](#new)
    * You can now add custom `id` tags to the tables (thanks to @mwender).
1. [](#improved)
    * Improved error reporting when things go wrong.

# v2.1.5
## 07/06/2017

1. [](#bugfix)
    * Fixed a bug with abbreviated shortcodes ('[ti=filename.ext/]').

# v2.1.4
## 05/05/2017

1. [](#bugfix)
    * Fixed a bug with `data://` calls. Thanks, @kyleblanker!

# v2.1.3
## 01/31/2017

1. [](#bugfix)
    * Fixed a problem with the code not running with the Admin plugin installed.

# v2.1.2
## 11/08/2016

1. [](#new)
    * Added a `caption` option to add a caption to the table.

# v2.0.2
## 11/07/2016

1. [](#bugfix)
    * The `trim` functions don't work the way I thought they did. Fixed it.

# v2.0.1
## 11/05/2016

1. [](#new)
    * Added `raw` option to allow you to include HTML in your table data.

# v2.0.0
## 11/05/2016

1. [](#improved)
    * Moved to `shortcode-core` interface. NOT BACKWARDS COMPATIBLE! Please read the revised documentation.

# v1.0.2
## 10/06/2016

1. [](#bugfix)
    * Fixed a bug that occurred when no options were passed (issue #2)

# v1.0.1
##  09/30/2016

1. [](#new)
    * Added demo URL

# v1.0.0
##  09/26/2016

1. [](#new)
    * ChangeLog started...

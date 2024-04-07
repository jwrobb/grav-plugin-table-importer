# Roadmap
### Version 2.3
1. Get acquainted with the code base and building Grav plugins
2. Update the League CSV library to latest, doesn't work with PHP 8
3. Add information about adding unsupported file types to the pages in Grav config
4. Update the plugin to follow current Grav plugin standards


## Future
Just some thoughts on where we might take this plugin, just brain dumping, not all are great ideas
* Default tables styles
   * Just a few basic options for those that don't want/need special styling
* Support inline styles?
   * Might pair nice with overriding some of the default styling?
* Tighter integration with jQuery datatables
   * Currently doesn't play nice with the datatables plugin
   * https://github.com/finanalyst/grav-plugin-datatables
   * There are a bunch of other table plugins we could explore tighter integration with
* Sticky headers/footer rows
* Custom attribute/value pairs
   * e.g.
      * attr=data-cell attrval=name <-- Same name/value pair for all cells
      * attr=data-cell attrval=name|age|height <-- Iterate through values
* Custom style block for the table
* Support pulling the data file from a place that cannot be accessed by the browser
   * I think the user/data directory does this, but getting the file in there isn't straightforward
* MUST - Upgrade to latest version of the League CSV library, current ver incompatible with PHP 8
* 
  

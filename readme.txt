=== Brad's Entity Attribute Value Database ===
Contributors: mobilebsmith
Donate link:
Plugin URI: http://mobilebsmith.hopto.org
Tags: database design forms entity-attribute-value
Author: Bradley Smith
Requires PHP: 7.0
Requires at least: 5.6
Tested up to: 5.7
Stable tag: 2.13
Version: 2.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to create an entity-atribute-value database for your custom data you would like to store

== Description ==

Welcome to Brad's Entity Attribute Value Database
This welcome page is where I will place ongoing information when I update this plugin. Also this will (I hope) have enough documentation to get you started. So what is an Entity Attribute Value Database? Well the easiest way is to have you read about with the links below, and if you have any other please let me know.

https://blog.greglow.com/2018/02/12/sql-design-entity-attribute-value-tables-part-1/
https://blog.greglow.com/2018/02/19/sql-design-entity-attribute-value-tables-part-2-pros-cons/
https://en.wikipedia.org/wiki/Entity-attribute-value_model

Okay so inshort this plugin is meant to allow people to track items (people,cars,etc) without the need to create a database table for each thing. All data is currently stored in 4 tables.

As things progress hopefully I will get default values, incrementing values, and many other things going. This is of course the first plugin I have released, so as always I am looking for ways to do things better.

This first version is more of a proof of concept and to see what others think as I develop more.

Okay so first there is very small amount of error checking, and onto the help section:

Admin Pages
	Manage Records - This is where you will define the record names you want to keep things in
	Mange Attributes - This is where you will define your fields
	Manage Record Layout - This is where you will define what fields are in each of your records
	SQL Default - This is where you will define sql for defaulting values

shortcodes - standalone
[eav_tbl table=tablenamehere] - currently this shows all the rows in for the table in the argument
	- there is an additional option allowupd=y will allow the person to update the record
	- also you can use flds=2,4,6 which will only show fields 2,4,6 - order must be low to high
[eav_add table=tablenamehere] - currently this allows you insert values into the table in the argument 

shortcodes - group1
[eav_startadd table=tablenamehere] - this shortcode starts the selective data entry
[eav_field field=fieldnamehere] - this shortcode places the an entry box for tablenamehere.fieldnamehere.  You can also add a "hidden=y" value as well and this field will be of type hidden instead of text.
[eav_subrec table=subtablehere] - this shortcode places an entry row for a child record on the page
[eav_endadd] - this is the shortcode you use to close the form, you can only have 1 eav_startadd/eav_endadd combination

There are 2 demo apps that you can see how things are done
- Guest Registration, this will create a all the fields, records and a page which will show you how to create a data entry page
- Apache Log, this will install record,field that you can now use the apache shortcut to load apache web data

== Frequently Asked Questions ==
= What does this plugin allow you to do? =
By using the 3 admin pages, you can create records and fields to hold your data.  Because of the way the data is stored, you do no need to create tables in the database. Each row of data you enter will have a unique id that is automatically assigned.  

= How does search work? =
Because of the way the data is stored, when you search for a value it searchs all fields for your search value. You no longer need to search on specific fields.

= How do I use [eav_tbl]? =
[eav_tbl] shortcode has a required option and there is an optional option. The required option is the "table=" option.  This option is used to tell the code what table you would like to browse. If you only use the table option, the shortcode allows the user to click on a row of data and view all data for a single record. 

Currently the only optional option is "allowupd=", and this option if set allows the user to select a record and update that record.

= How do I use [eav_add]? =
[eav_add] shortcode has a required option of "table=", which holds the table you want to add a record to.

= How do I use group1 shortcodes? =
Group 1 shortcodes are a group of shortcodes that work together to allow you to dynamically place fields on a page for data entry. So by placing these shortcodes on different spots on the page you could have graphics and text which explain what the end user is doing. [eav_startadd] shortcode must be the first shortcode on the page to be used, and [eav_endadd] must be the last shortcode used. [eav_field] must contain a field that is on the record, having a non field value might create an error on the page, also only have a field on the page once. Parent/Child records, is on the first version and more is coming on it. But currently this shortcode allows you to enter multiple child rows into the child record. Also only 1 child record entry is supported on the page at the time.

= What does the attribute format do? =
The format value is currently only used when presenting the field data.  So in group1 it allows you to size the data entry area. Other than that, this value does not limit the amount of data entered.

= How many characters do the fields store? =
Currently 128 characters is the field storage limit.  This may change as the plugin is developed.

== Screenshots ==

1. This screen shot shows how you could use group 1 shortcodes. 


== Changelog ==
= v2.13 [6/30/2021]
* Changed setup screens to be together instead of separate pages
* fixed search to work with a child record. Child-of-Child still has issues, that fix is coming
* enhanced drill down from eav_tbl now shows all child records automatically.  More fixes coming to this as well.
* have all calls check ob_get_level() to determine if we need to call it. This allows other functions to call my functions.
* add some demo apps, could use work on these.
* added keys to eav_entity table to help in search performance.

= v2.12 [5/31/2021]
* some bug fixes on eav_tbl
* add load= option to eav_tbl shortcode
* we now use jquery table to present tables and allow paging.

= v2.11 [5/28/2021]
* fixed package, trunk was included - must be still learning


= v2.10 [5/28/2021]
* updated/fixed/add shortcuts

= v2.09 [4/1/2021]
* updated tags incorrect - still learning.

= v2.08 [4/1/2021]
* updated tags incorrect - still learning.

= v2.07 [4/1/2021]
* added being able to default field values via SQL
* subrecords and table view now have scroll bar if the screen is not large enough
* tested on older version of wordpress

= v2.06 [3/28/2021]
* bug fixes on shortcodes for eav_tbl and eav_subrec

= v2.05 [3/28/2021]
* bug fixes for missing prefix on tables

= v2.04 [3/26/2021]
* include directory missing - all v2.X still apply

= v2.03 [3/26/2021]
* still learning how to check in and update version


= v2.02 [3/26/2021]
* possible update due to wrong comment/commit
* still need to delete old plugin if on version 1.0


= v2.01 [3/26/2021]
* possible update due to wrong comment

= v2.00 [3/26/2021]
* complete overhall on php file layout
* changed table names to include $wpdb->base_prefix
* added some automatic defaults #user, #now , #today
* need to delete old plugin before installing this one.

= v1.00 [3/25/2021]
* added features to [eav_tbl] shortcode
* new [eav_startadd] shortcode
* new [eav_field] shortcode
* new [eav_subrec] shortcode
* new [eav_endadd] shortcode
* added screenshot to manifest
* fixed some bugs
* updated FAQ

= v.09 [03/11/2021]
* added features to editing attributes (name, description)
* added some error checking to not allow duplicates

= v.08 [03/10/2021] 
* Initial released
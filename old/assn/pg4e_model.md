Designing a Data Model
======================

In this assignment you will
develop a data model from a file of un-normalized data.  Later we will load and
normalize the data.

The data is a simplified extraction
of the <a href="https://whc.unesco.org/en/list/" tatget="_blank">UNESCO World Heritage Sites</a> registry.
The un-normalized data is provided as both a spreadsheet and a CSF file:

<a href="pg4e_model/whc-sites-2018-clean.csv" target="_blank">CSV Version</a>

<a href="pg4e_model/whc-sites-2018-small.xls" target="_blank">XLS Version</a>

The columns in the data are as follows:

    name,description,justification,year,longitude,latitude,
    area_hectares,category,states,region,iso

Vertical Replication
--------------------

You are to design a database model that represents this flat data across
multiple tables using "third-normal form" - which basically means that
columns that have vertical duplication, such as region need to be placed
in their own table and linked into the main table using a foreign key.

    category    states                 region                      iso

    Cultural    Afghanistan            Asia and the Pacific        af
    Cultural    Afghanistan            Asia and the Pacific        af
    Cultural    Albania                Europe and North America    al
    Cultural    Albania                Europe and North America    al
    Cultural    Algeria                Arab States                 dz
    Mixed       Algeria                Arab States                 dz
    Cultural    Algeria                Arab States                 dz
    Cultural    Algeria                Arab States                 dz

You will model diagram that describes the tables, one-to-many relationships,
and foreign keys sufficient to represent this data efficiently with no vertical duplication.
This assignment does not need any many-to-many relationships.

Name the first table `site`, use singular names for all of the table
names.  Use the exact name of the column for the model field names and
foreign key names.   Even though the data labels one column as `states`,
name your table `state` and the foreign key `state_id`.

What to Turn In
---------------

(1) Create a picture of the tables, fields, and relationships.  Make sure all the columns 
in the original data are represented in the diagram.  You can use an online diagramming tool
like https://dbdiagram.io/home or you can draw a picture on paper, take a phot and
turn in an image.

(2) Develop all of the `CREATE TABLE` statements needed to construct the tables following
the conventions for field naming used in the lectures.  (a) Primary keys should be `id`,
(b) logical keys should be `UNIQUE NOT NULL`, and (c) foreign keys should be named `table_id` based
on the name of the destination table.  You should make sure your create statements work
by running then in PostgreSQL.


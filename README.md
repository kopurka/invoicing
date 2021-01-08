# invoicing
PHP / JS application that lets you sum invoice documents in different currencies via a file

We have a CSV file, containing a list of invoices and credit notes in different currencies. 
Example file could be found in /tests/ directory. There are 2 CSV files.
- first one is with correct syntax (test.csv)
- second one is with wrong syntax/data for test purposes (bad_format.csv)

IMPORTANT
All information is stored in memory. 
For data we use mysqlite (memory)
For caching we use redis

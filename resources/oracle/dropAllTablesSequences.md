The following plsql script can be used to drop all tables and sequences 
for an existing schema/user. 
see: [drop all user tables sequences oracle](http://stackoverflow.com/questions/2549718/dropping-all-user-tables-sequences-in-oracle)

```
BEGIN

  --Bye Sequences!
  FOR i IN (SELECT us.sequence_name
              FROM USER_SEQUENCES us) LOOP
    EXECUTE IMMEDIATE 'drop sequence '|| i.sequence_name ||'';
  END LOOP;

  --Bye Tables!
  FOR i IN (SELECT ut.table_name
              FROM USER_TABLES ut) LOOP
    EXECUTE IMMEDIATE 'drop table '|| i.table_name ||' CASCADE CONSTRAINTS ';
  END LOOP;

END;
```
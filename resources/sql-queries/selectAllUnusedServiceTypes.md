Select all service types that are unused (i.e. those which don't 
link/join to a service instance). 

```sql
select st.name
from servicetypes st
left join SERVICES se on st.id = se.servicetype_id
where
se.servicetype_id IS NULL
; 
```
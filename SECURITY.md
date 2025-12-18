# Security Policy

## Security Philosophy

Relay is designed with security as a top priority. This document outlines the security features built into Relay and best practices for maintaining a secure installation.

## Built-in Security Features

### 1. Authentication & Authorization

**Password Security:**
- Argon2ID password hashing (with BCrypt fallback)
- Minimum 8-character password requirement
- No password stored in plain text

**Session Security:**
- HTTP-only cookies prevent XSS cookie theft
- Strict SameSite policy prevents CSRF via cookies
- Session regeneration on login prevents session fixation
- Configurable session timeout (default: 30 minutes)
- Session validation on every request

**Rate Limiting:**
- Login attempts limited to 5 per 15 minutes per session
- Automatic lockout after failed attempts
- Clear feedback on lockout duration

### 2. CSRF Protection

All state-changing operations are protected against Cross-Site Request Forgery:

- Unique token generated per session
- Token validation on all POST requests
- Token age verification (2-hour expiration)
- Both form fields and meta tags supported

### 3. Input Validation & Sanitization

**Username Validation:**
- Alphanumeric and underscore only
- Prevents command injection attempts

**Path Sanitization:**
- Removes path traversal sequences (.., /)
- Blocks null bytes
- Validates against whitelist patterns
- Real path verification ensures paths stay within content directory

**Output Encoding:**
- HTML entity encoding for all user-generated content
- JSON encoding for API responses
- Prevents XSS attacks

### 4. File System Security

**Directory Protection:**
- `.htaccess` files deny direct access to `/content` and `/config`
- Apache configuration blocks sensitive files
- nginx configuration examples provided

**File Permission Recommendations:**
```
Directories: 755 (rwxr-xr-x)
PHP files: 644 (rw-r--r--)
Config files: 640 (rw-r----) - if web server user differs from owner
Content files: 644 (rw-r--r--)
```

### 5. HTTP Security Headers

**Default Headers (via .htaccess):**
- `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - Enables browser XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer information

### 6. Dependency Security

**Minimal Dependencies:**
- Only two dependencies: Parsedown and Parsedown Extra
- Regularly updated via Composer
- No client-side JavaScript frameworks (admin uses vanilla JS)

## Security Best Practices

### Deployment Security

1. **HTTPS Only**
   - Always use HTTPS in production
   - Configure SSL/TLS with strong ciphers
   - Enable HSTS headers
   - Redirect HTTP to HTTPS

2. **Strong Passwords**
   - Minimum 12 characters recommended
   - Mix of uppercase, lowercase, numbers, symbols
   - Avoid common words or patterns
   - Use password manager for generation

3. **User Management**
   - Create users with least privilege (use 'editor' role when possible)
   - Regularly review user list
   - Remove unused accounts promptly
   - Never share credentials

4. **File Permissions**
   ```bash
   # Set directory permissions
   find /path/to/relay -type d -exec chmod 755 {} \;

   # Set file permissions
   find /path/to/relay -type f -exec chmod 644 {} \;

   # Restrict config directory (if web server runs as www-data)
   chown -R www-data:www-data /path/to/relay/config
   chmod 750 /path/to/relay/config
   chmod 640 /path/to/relay/config/*.json
   ```

5. **Regular Updates**
   - Keep PHP updated to latest stable version
   - Update Composer dependencies regularly: `composer update`
   - Monitor security advisories for Parsedown
   - Apply security patches promptly

6. **Backup Strategy**
   - Regular automated backups of `/content` and `/config`
   - Store backups securely and encrypted
   - Test restoration process regularly
   - Keep backups off-site or in separate infrastructure

### Monitoring & Auditing

1. **Log Monitoring**
   - Review Apache/nginx access logs regularly
   - Monitor PHP error logs for unusual activity
   - Look for repeated failed login attempts
   - Alert on suspicious patterns

2. **File Integrity**
   - Monitor changes to PHP files
   - Use file integrity monitoring tools (e.g., AIDE, Tripwire)
   - Alert on unexpected modifications
   - Regularly verify .htaccess files

3. **Security Scanning**
   - Run regular vulnerability scans
   - Check for outdated dependencies: `composer outdated`
   - Use tools like `phpcs` for code security analysis
   - Perform periodic penetration testing

### Configuration Hardening

1. **PHP Configuration** (`php.ini`):
   ```ini
   expose_php = Off
   display_errors = Off
   log_errors = On
   error_log = /var/log/php/error.log
   session.cookie_httponly = 1
   session.cookie_secure = 1  # If using HTTPS
   session.cookie_samesite = Strict
   session.use_strict_mode = 1
   upload_max_filesize = 2M  # Relay doesn't use uploads, keep low
   post_max_size = 2M
   disable_functions = exec,passthru,shell_exec,system,proc_open,popen
   ```

2. **Apache Hardening** (add to .htaccess or Apache config):
   ```apache
   # Disable server signature
   ServerSignature Off
   ServerTokens Prod

   # Additional security headers
   Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
   Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';"
   Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
   ```

3. **Docker Security**:
   ```yaml
   # Run as non-root user
   user: "1000:1000"

   # Read-only root filesystem
   read_only: true
   tmpfs:
     - /tmp
     - /var/tmp

   # Limit resources
   mem_limit: 256m
   cpus: 0.5
   ```

## Known Limitations

### Intentional Design Decisions

1. **No Content Editing UI**: Content is managed via file system to maintain simplicity and security. This prevents web-based file upload vulnerabilities.

2. **No Image Upload**: Image management is handled via file system or external services. Prevents upload-based attacks.

3. **CLI User Management**: User operations require server access, preventing unauthorized user creation via web exploits.

4. **Single-Server Design**: Not designed for horizontal scaling. Use single instance with proper backups.

### Areas Requiring External Security

1. **Network Security**: Relay doesn't include WAF or DDoS protection. Use external solutions.

2. **Brute Force Protection**: Rate limiting is per-session only. Use fail2ban or similar for IP-based blocking.

3. **Database Security**: Not applicable (no database), but protects against SQL injection by design.

4. **File Upload Validation**: Not applicable (no uploads), but prevents this entire class of vulnerabilities.

## Reporting Security Vulnerabilities

If you discover a security vulnerability in Relay, please report it responsibly:

1. **Do not** disclose publicly until a fix is available
2. Email security details to: [security@yourorg.com]
3. Include:
   - Detailed description of the vulnerability
   - Steps to reproduce
   - Potential impact assessment
   - Suggested fix (if available)
4. Allow reasonable time for fix development and deployment
5. Coordinate disclosure timing with maintainers

## Security Checklist for Production

- [ ] HTTPS configured with valid certificate
- [ ] Strong admin password set (12+ characters)
- [ ] Default credentials changed
- [ ] File permissions properly configured
- [ ] `.htaccess` files in place and working
- [ ] PHP display_errors disabled
- [ ] Error logging configured
- [ ] Regular backups scheduled
- [ ] Log monitoring in place
- [ ] Security headers verified
- [ ] Composer dependencies up to date
- [ ] Unnecessary users removed
- [ ] Server signature disabled
- [ ] Rate limiting tested
- [ ] Session timeout configured appropriately

## Security Updates

Security updates will be released as needed and announced via:
- GitHub Security Advisories
- Release notes
- Project mailing list (if available)

Always test updates in a staging environment before applying to production.

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html)
- [Docker Security Best Practices](https://docs.docker.com/engine/security/)

---

Last updated: 2024-12-17

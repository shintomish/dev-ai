# Deployment History

## 2026-01-14: Sales Mode Deployment

### Environment
- Server: Kagoya VPS
- Domain: https://ai-mon.net/
- SSL: Let's Encrypt
- Web Server: GitLab Nginx
- PHP: 8.2 + PHP-FPM
- Database: MariaDB

### Deployed Features
- Sales support mode (営業支援モード)
- User-specific tags with color selection
- Database migration: conversations.mode VARCHAR(20)
- Database migration: tags.user_id

### Configuration Changes
- PHP-FPM socket ACL: added gitlab-www user
- Composer: production optimization
- Laravel: config/route/view cache

### Performance
- Response time: Improved ✅
- OPcache: Enabled ✅
- Autoloader: Optimized ✅

### Issues Resolved
- 502 Bad Gateway (PHP-FPM socket permissions)
- ACL configuration for GitLab Nginx

### Status
✅ Deployment successful
✅ All features working
✅ Performance optimized

# Git Branch Naming Convention

## Format
```
<type>/<scope>-<description>
```

## Tipe Branch
- `feature/` - Fitur baru
  - Contoh: `feature/auth-login`, `feature/role-authorization`
- `fix/` - Perbaikan bug
  - Contoh: `fix/policy-authorization`, `fix/middleware-role`
- `hotfix/` - Perbaikan kritis
  - Contoh: `hotfix/security-auth`
- `docs/` - Dokumentasi
  - Contoh: `docs/api-documentation`

## Scope (Opsional)
- `auth` - Authentication
- `user` - User management
- `policy` - Authorization policy
- `role` - Role-based access
- `middleware` - HTTP middleware
- `controller` - Controller logic
- `view` - View/UI

## Contoh yang Valid
```
feature/auth-login
feature/role-member
fix/policy-user
fix/middleware-admin
hotfix/security-role-check
docs/api-auth
```

## Perintah Umum

### Mulai Fitur Baru
```bash
./git-helper.sh start feature/auth-login
# atau
git checkout -b feature/auth-login
```

### Commit Perubahan
```bash
./git-helper.sh commit "feat: Tambah login authentication"
# atau
git add .
git commit -m "feat: Tambah login authentication"
```

### Push ke Origin
```bash
./git-helper.sh push
# atau
git push origin <branch-name>
```

### Merge ke Main
```bash
# Setelah PR disetujui
./git-helper.sh merge
# atau
git checkout main
git pull origin main
git merge --no-ff feature/auth-login
git push origin main
```

## Konvensi Commit Message

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Type
- `feat` - Fitur baru
- `fix` - Perbaikan bug
- `docs` - Dokumentasi
- `style` - Formatting, whitespace
- `refactor` - Perbaikan struktur kode
- `test` - Tambah/modifikasi test
- `chore` - Maintenance, build, dll

### Contoh
```
feat(auth): Tambah login dengan role-based middleware

- Implementasi LoginController
- Tambah RoleMiddleware
- Update UserPolicy

Fixes #123
```

## Pull Request Template

Gunakan template di `git-helper.sh pr` untuk membuat PR yang komprehensif.

## Best Practices

1. **Jaga Branch Up-to-Date**
   - Sebelum mulai kerja, selalu pull dari main
   - Setiap hari, rebase ke main

2. **Commit yang Kecil dan Relevan**
   - Setiap commit harus merepresentasikan satu perubahan logis
   - Gunakan pesan commit yang jelas

3. **Jangan Commit File yang Tidak Perlu**
   - `.env`, `node_modules`, `vendor/`
   - Tambahkan ke `.gitignore`

4. **Review Code Sebelum Merge**
   - Pastikan tidak ada konflik
   - Test authorization semua role
   - Cek policy compliance

5. **Test Role Authorization**
   - Member tidak boleh akses admin
   - Technician hanya lihat pekerjaan sendiri
   - Admin tidak boleh akses super admin
   - Super Admin memiliki akses penuh
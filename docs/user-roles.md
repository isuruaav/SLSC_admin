# User roles

Open **Administration → Roles & Permissions** as a Super Administrator. Open **Users → Create User / Edit User** to assign a role. The role selector previews the role's current access and allowed actions.

| Role | Recommended use |
| --- | --- |
| Super Administrator | Site owner; full access, including roles and permissions. |
| Site Administrator | Day-to-day website operations, user accounts, settings, menus and content. Cannot administer protected accounts or change role permissions. |
| Publisher | Review and publish pages, news, galleries and documents. |
| Content Editor | Prepare content, upload images and submit pages/news for review. Does not publish. Recommended for most content staff. |
| Media Operator | Manage images, galleries and documents, including replacing/deleting library media. Does not publish. |
| Auditor | Read-only content/user inspection and activity logs. |

These are starting templates. Existing customizations remain in place. Review **Current role access → View allowed actions** when assigning a customized role. User-specific permissions can add access beyond the selected role.

**Site Settings → Site Languages** uses the existing `settings.manage` permission. Super Administrator and Site Administrator presets already include this access. Header and footer editing continues to use `theme-layouts.manage`. Language selection does not require granting additional access to content roles.

## Recommended roles and custom roles

- **Add recommended roles** creates only missing roles; existing role grants and user assignments stay intact.
- **Create a custom role** copies a recommended access template, or starts blank. Super Administrator cannot be copied as a template.
- Select a role, find a module/action with **Find access**, and use its checkboxes or **Select all / Clear group**.
- Required access is selected automatically. Removing a prerequisite also removes the actions that require it.
- **Load template** changes the selection only. **Save Permissions** applies it to everyone assigned to the role. **Discard changes** reloads saved access.
- Role changes and role creation are recorded in activity logs. Only Super Administrators may change role definitions.

## Existing installations

After replacing the changed application files, install missing roles with:

```bash
php artisan roles:setup
```

The command registers missing permissions and creates missing recommended roles. It preserves existing grants, custom roles, direct permissions and user assignments. It does not create users or passwords. It is safe to repeat; no schema migration or dependency installation is required for this feature.

`RolesAndPermissionsSeeder` is for explicit initialization/reset and restores recommended grants for existing built-in roles. Use `roles:setup` to preserve customizations on an existing installation.

Account protections remain active: non-super administrators cannot assign stronger/protected roles or manage stronger accounts; the last active Super Administrator cannot be demoted/disabled; users cannot disable their own account.

## Deleting users and changing your password

**Users → Delete user** is available with `users.delete`. The Super Administrator preset already includes this permission. Site Administrators can create and disable accounts; account deletion requires an explicit additional grant. Confirming deletion permanently removes the login account, its sessions, password-reset links and passkeys. Existing pages, news and audit history stay available. You cannot delete your own account or the last active Super Administrator. Non-super administrators cannot delete protected or stronger accounts. Deletions are audited.

**My Account → Change Password**, or **Password** in the admin header, changes your own password. It is available to signed-in administrators, including Super Administrators, without permission to reset other users' passwords. **Edit your own user → Change My Password** opens the same page. Enter your current password, a new password of at least 12 characters with uppercase/lowercase letters, a number and a symbol, and confirmation. Your browser stays signed in; other database sessions and outstanding reset links are invalidated. Two-factor authentication and passkeys remain available. Password values are never included in the audit event.

Resetting another user's password continues to use **Users → Edit → Secure Password Reset** and requires `users.reset-password` plus the administrator's current password. The personal profile delete form also prevents Super Administrators from deleting their own account.

# Form Submissions for ImpressPages

Keeps a copy of every message sent through a **Form widget**, with its attachments, so you can read, answer and export them in the admin, even when an email goes missing. The email is still sent exactly as before.

## Features

- Every successful Form widget submission is saved: the fields with their labels, the page it came from, and attachments.
- A list in the admin, newest first, with unread messages marked and a short preview.
- Open a submission to read all fields, download attachments, reply by email or mark it unread.
- Download everything as a CSV file for a spreadsheet (UTF-8, with protection against formulas in visitors' text).
- Delete one submission or all of them; old submissions are deleted after a number of days you choose.
- Visitor IP addresses are not stored unless you turn that on.
- Works with any Form widget without changing it: no core changes, nothing to set up per form.

## Requirements

ImpressPages 5.1 or newer (the PHP 8 community fork) on PHP 8.1 or newer, with MySQL or MariaDB.

## Install

1. Copy this repository into your site's `Plugin` folder as **`Plugin/FormSubmissions`** (the folder name must be `FormSubmissions`):
   ```bash
   git clone https://github.com/isunktitanic/impresspages-plugin-formsubmissions.git Plugin/FormSubmissions
   ```
2. In the admin, open **Plugins**, select **Form Submissions** and click **Activate**. This creates the `formsubmissions_entry` table.
3. Open **Form Submissions** in the admin menu (under Plugins). New messages appear as soon as a visitor sends a form.

## Settings

| Setting | What it does |
|---|---|
| Delete submissions after (days) | 365 by default; 0 keeps them until you delete them. |
| Store the visitor's IP address | Off by default. It's personal data; only turn it on if your privacy policy covers it. |
| Keep attachments | On by default. Files are copied into `file/secure/formsubmissions/`. |

## Notes

- Submissions are personal data. Mention in your privacy policy that messages are stored, and for how long.
- Attachments are stored in folders with random names under `file/secure/`, which ImpressPages protects with an `.htaccess` rule. Some hosts (for example LiteSpeed servers that ignore `.htaccess`) don't apply that rule; the random names keep files from being guessed, but if your host is one of them, turn **Keep attachments** off or check the folder isn't reachable.
- Attachments can only be downloaded by administrators who can open the plugin.
- Deactivating keeps the stored submissions; removing the plugin deletes them and their attachments.

## License

MIT, see [LICENSE](LICENSE).

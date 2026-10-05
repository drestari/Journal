AJSMR DYNAMIC ARTICLE PUBLICATION MODULE V1

Purpose
-------
Allows the Editor-in-Chief to enter, edit and publish AJSMR article records
without changing the existing manuscript-review workflow.

Package
-------
article_add.php       Editor-in-Chief article entry
article_edit.php      Editor-in-Chief article editing
article_manage.php    Article search/list
article.php           Public article/abstract page
article_common.php    Shared security/upload/database helpers
sql/ajsmr_articles_v1.sql
uploads/articles/.htaccess

INSTALLATION
------------
1. BACK UP the existing AJSMR editorial database.
2. Upload the PHP files into:
   /public_html/ajsmrjournal.com/editorial/
3. Upload the uploads/articles/.htaccess file and keep the directory writable
   by PHP (normally 0755/0750 depending on the hosting configuration).
4. Run sql/ajsmr_articles_v1.sql against the EXISTING `ajsmr_editorial`
   database.
5. Do NOT replace config/config.php with a package copy. Keep your existing
   database credentials.
6. Log in using an account whose role is exactly:
   editor_in_chief
7. Open:
   article_add.php
8. After saving, manage records at:
   article_manage.php
9. Published records are publicly available at:
   article.php?id=AJSMR-YYYY-XXXXX

IMPORTANT
---------
- Article entry is restricted to Editor-in-Chief.
- Public article.php only displays PUBLISHED and UPDATED records.
- Existing manuscript tables are not altered by the SQL.
- Main article PDF is limited to 25 MB.
- Do not put PHP scripts inside uploads/articles/.
- Test on the local AJSMR/XAMPP copy before production upload.

EDITORIAL DASHBOARD LINK
------------------------
Add a dashboard menu item for Editor-in-Chief:
  <a href="article_manage.php">Article Management</a>
or a prominent button:
  <a href="article_add.php">Add Published Article</a>

NOTE ABOUT THE SAMPLE ARTICLE PAGE
----------------------------------
The public article page follows the content structure of the supplied
abstractpage.html model: article type, title, authors/affiliations, article
metadata, DOI, abstract, keywords, article information, files, how to cite,
references, and journal sidebar. The header/navigation/footer are AJSMR's
own design rather than the source journal's design.

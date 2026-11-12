# Cookbook — IDM 232

**Quinn Kessler** · Drexel University · Fall 2026

## What is this project?

A cookbook web app built with PHP and MySQL, for home cooks looking for a recipe to make. It stores 40 recipes in a relational database (categories, recipes, and each recipe's ingredients, steps, tools, tips and photos). Visitors can browse every recipe, search by name or ingredient, filter by category, and open a full recipe page with its ingredients and step-by-step instructions, all without logging in. An admin logs in to a protected admin area to add, edit and delete recipes.

## How do I run it locally?

1. Install **XAMPP** or **MAMP** and start Apache and MySQL.
2. Put this project folder inside your `htdocs` folder (XAMPP: `xampp/htdocs`, MAMP: `MAMP/htdocs`).
3. Open phpMyAdmin, go to the **Import** tab and load `database/schema.sql`. It is a phpMyAdmin export of the whole database (with **Add CREATE DATABASE / USE statement**), so it creates the `idm232_cookbook` database and all its tables and data by itself; no database needs to exist first. To import it again later, first drop the existing `idm232_cookbook` database (phpMyAdmin: select it, then **Operations** > **Drop the database**).
4. Make a copy of `config.example.php` in the same folder, name it `config.php`, and fill in your database login. Usual defaults: XAMPP is port `3306`, user `root`, empty password; MAMP is port `8889`, user `root`, password `root`.
5. Open the recipe list in your browser, using your project folder's name:
   - XAMPP: `http://localhost/<project-folder>/alpha/recipes.php`
   - MAMP: `http://localhost:8888/<project-folder>/alpha/recipes.php`
6. To use the admin area, see **Admin login** below.

## Admin login

The admin account is **seeded** by `schema.sql` (there is no registration page):

- Email: `admin@cookbook.test`
- Password: `cookbook-admin-2026`

Click **Log in** in the navigation. After logging in, **Admin** opens the recipe admin, where you can add, edit and delete recipes. This account is for local testing only; the database stores only its `password_hash()` result, never the password.

## Which feature did you build?

**Search.** Visitors can find recipes by typing words into the search box on the recipe library page.

How to use it:

1. Open `alpha/recipes.php` (no login needed).
2. Type a word into **Search by name or ingredient**, for example `garlic` or `pasta`, and click **Search**.
3. The list shows only recipes whose title, subtitle, description or ingredient names contain that word, with a count such as "6 recipes matching "pasta"". Click **Clear** to see every recipe again.

How it works: `find_recipes()` in `includes/recipe_queries.php` passes the search words to MySQL only through a prepared statement with `LIKE ?` parameters (never pasted into the SQL), and escapes `%` and `_` so they are searched as normal characters.

A **category filter** is also included as an extra: the category links above the list (All, Beef, Chicken and so on) narrow the results, and work together with search.

## Features

- **Browse:** `alpha/recipes.php` lists every recipe with its photo, category and servings.
- **Full CRUD on recipes (admin only):**
  - **Create:** `alpha/admin/create.php` adds a recipe (title, subtitle, category, servings, description).
  - **Read:** the admin list (`alpha/admin/index.php`) and the public pages.
  - **Update:** `alpha/admin/edit.php?id=1` edits a recipe's details.
  - **Delete:** `alpha/admin/delete.php?id=1` asks for confirmation, then deletes the recipe and, through the foreign keys, its ingredients, steps, tip and image rows.
  - New recipes start without photos, ingredients or steps, and the public pages show placeholders for them. Editing ingredients and steps, and uploading photos, come next.
- **Recipe detail:** `alpha/recipe.php?id=1` shows one recipe's full content: description, ingredients, numbered steps with photos, its tip and its kitchen tool. Unknown ids show a "Recipe not found" page with a 404 status.
- **Admin login:** `alpha/login.php` checks the email and password and starts a session. Every page in `alpha/admin/` is only reachable when logged in; anyone else is redirected to the login page. `alpha/logout.php` ends the session.
- **Light and dark mode**, and a recipe form with server-side validation (`alpha/index.php`).

## What's in the repo

Config, logic and markup are kept separate:

| Path | What it is |
|------|------------|
| `config.example.php` | Template for `config.php`, which holds the database login and is gitignored. |
| `database/schema.sql` | Creates all tables and seeds 6 categories, 40 recipes and one demo admin account. Every table has an `AUTO_INCREMENT` primary key. `recipes.category_id` is a foreign key to `categories`, and the other tables link to `recipes` by `recipe_id`. |
| `includes/db.php` | Connects with mysqli in exception mode (`MYSQLI_REPORT_ERROR \| MYSQLI_REPORT_STRICT`). |
| `includes/recipe_queries.php` | All database read functions (list, search, categories, one recipe and its ingredients, steps, tip and tools). |
| `includes/recipe_writes.php` | Recipe form validation and the create, update and delete functions. |
| `includes/auth.php` | Starts the session with a hardened cookie, logs users in and out, guards admin pages (`require_login()`), makes and checks CSRF tokens, and holds one-time messages ("Added ...") between pages. |
| `includes/user_queries.php` | Looks up accounts by email or id. |
| `includes/helpers.php` | `e()` for escaping output, `page_link()` for building links, `post_value()` for reading form fields. |
| `includes/theme.php` | Light/dark mode. Only accepts `light` or `dark`. |
| `templates/header.php`, `templates/footer.php` | Shared page top (head, navigation) and bottom. |
| `templates/recipe_form.php` | The add / edit recipe form, shared by both admin pages. |
| `alpha/recipes.php` | Recipe library page: browse, search and filter. |
| `alpha/recipe.php` | Recipe detail page. |
| `alpha/login.php`, `alpha/logout.php` | Log in and log out. |
| `alpha/admin/index.php` | Recipe admin: every recipe with Edit and Delete links, and Add a recipe. |
| `alpha/admin/create.php`, `edit.php`, `delete.php` | Add, edit and delete a recipe. All guarded by the login check. |
| `alpha/index.php` | Recipe form with server-side validation (week 2). |
| `alpha/images/` | Recipe photos. Paths are stored in the database. |

## Data access and security

- **Every value from the visitor reaches SQL through a `?` placeholder with `mysqli_execute_query`:** the search words, the category id, the recipe id, and every field of the add and edit forms. No variable is ever pasted into a SQL string. Only queries with no variables at all (the category list) use `mysqli_query`.
- **Search and filter** use one function, `find_recipes()`. It builds its `WHERE` clause from fixed pieces of SQL and adds each value to the parameter array in the same order as its `?`. `%` and `_` in search words are escaped so they don't act as wildcards.
- **Input is validated first:** ids must be whole numbers of 1 or more (`filter_input` with `FILTER_VALIDATE_INT`); search text must be a string, is trimmed and is capped at 100 characters. Invalid input shows all recipes, or "Recipe not found".
- **Writes (`includes/recipe_writes.php`):** `INSERT`, `UPDATE` and `DELETE` are all prepared statements. `UPDATE` and `DELETE` always have `WHERE recipe_id = ?`, so they can only touch one recipe, and the admin pages check the recipe exists first (unknown ids get a 404).
- **Form validation:** the add and edit forms are checked on the server before anything is written: title required and at most 150 characters, subtitle at most 150, category must be one of the real categories, servings a whole number from 1 to 20, description required and at most 5,000 characters. Errors show next to each field and the typed values stay in the form. After a successful save the page redirects, so refreshing can't submit it twice.
- **Joins:** recipes are listed with their category name through an `INNER JOIN`; category counts use a `LEFT JOIN` with `COUNT` and `GROUP BY`; a recipe's tools come through the `recipe_tools` link table.
- **Passwords:** the `users` table stores only a `password_hash()` result (`VARCHAR(255)`), never the password. Login finds the account by email with a prepared statement; the password is never part of a query. PHP then checks it with `password_verify()`. Wrong email and wrong password show the same message, so the form doesn't reveal which emails have accounts.
- **Sessions:** at login, `session_regenerate_id(true)` swaps to a new session id and deletes the old one (prevents session fixation), and only the user id is stored in the session. The session cookie is `httponly` and `SameSite=Lax`, and `secure` when the site runs on HTTPS. Strict mode rejects session ids the server didn't create.
- **Admin guard:** `require_login()` sends visitors who aren't logged in to the login page with `header()` and then `exit`, so the protected page never renders.
- **CSRF:** the login, add, edit and delete forms carry a random per-session token, checked with `hash_equals()`. A missing or wrong token gets a 403. Delete only happens on a POST from the confirmation page, never from a plain link.
- Every value written to the page is escaped with `htmlspecialchars`.
- Database errors are caught, logged with `error_log`, and replaced with a generic message, so error details never reach the page.
- Every PHP file starts with `declare(strict_types=1);`.

## AI use

**AI tools used: Claude (Anthropic).** No other AI tools were used.

How Claude was used:

- **Recipe data:** turned the professor's recipe PDFs into the spreadsheet behind `schema.sql`, and filled in content cut off at page breaks: 135 ingredient lines, 71 step titles, 18 whole steps, one description and the tools for 3 recipes. AI ingredient quantities are estimates.
- **Database:** converted the spreadsheet into `schema.sql`, and added the `categories` table, foreign keys and `AUTO_INCREMENT` keys.
- **Code:** reviewed and fixed the week 2 form; wrote the files in `includes/` and `templates/`, `recipes.php` (category filter and search), `recipe.php` (detail page), the login, logout and admin pages (including add, edit and delete), and the CSS; and split shared code into `includes/` and `templates/`. Each of these files says so in a comment at the top.
- **Testing:** test-imported the schema into MySQL and checked the pages, including invalid and malicious input in the search box, category filter and recipe id, the login flow (wrong passwords, missing CSRF tokens, the admin guard and session id changes), and the admin create, edit and delete pages (invalid form input, logged-out access, missing tokens and unknown ids).
- **Learning and setup:** explained the code and concepts, walked through phpMyAdmin and git commands, and helped write this README.

# PHP Project Brief

**Project:** Event Registration System  
**Stack:** PHP Backend API + HTML/CSS/JS Frontend  
**Last Updated:** 2025-11-25

---

## Database Connection (PDO)

**DSN Format:**
```php
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
```

**Recommended Options:**
```php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Use native prepared statements
];
$pdo = new PDO($dsn, $user, $password, $options);
```

**Security:** Always use prepared statements with bound parameters to prevent SQL injection.

---

## API Response Headers

**Required for JSON APIs:**
```php
header('Content-Type: application/json');              // Indicate JSON response
header('Access-Control-Allow-Origin: *');              // Enable CORS (restrict in production)
```

**Production CORS (recommended):**
```php
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

## Q&A Log

### Q1: Explain events.php query logic (2025-11-25)
**Question:** How does the try-catch block work that fetches events from database?

**Code:**
```php
try {
    $stmt = $pdo->query("SELECT event_id, title, description, event_date FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch events']);
}
```

**Explanation:**
- Queries database for all events, sorted by date (earliest first)
- `fetchAll()` gets all rows as an array
- `json_encode()` converts to JSON for API response
- If error occurs, returns 500 status with error message

---

### Q2: Explain INSERT with placeholders (2025-11-25)
**Question:** How does `INSERT INTO events ... VALUES (?, ?, ?)` work?

**Code:**
```php
$sql = "INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$title, $description, $event_date]);
```

**Explanation (Simple Flow):**
1. **`$sql`** - Write the command with 3 placeholders (`?`)
2. **`prepare()`** - Holds the command and waits for 3 values
3. **`execute()`** - Give it the 3 values and run the command

**Analogy:** Like filling out a form
- `prepare()` = Get a blank form with empty fields
- `execute()` = Fill in the fields and submit it

**Why?** Security - separates the SQL structure from user data to prevent SQL injection attacks.

---

### Q3: Explain HTTP Response Headers (2025-11-25)
**Question:** What do these headers do in addevent.php?

**Code:**
```php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
```

**Explanation:**

1. **`Content-Type: application/json`**
   - Tells the client: "My response is in JSON format"
   - Helps browser parse the data correctly

2. **`Access-Control-Allow-Origin: *`**
   - Enables CORS (Cross-Origin Resource Sharing)
   - `*` = Any website can call this API
   - Needed when frontend and backend are on different domains/ports

3. **`Access-Control-Allow-Methods: POST`**
   - Only `POST` requests are allowed
   - Restricts which HTTP methods can access this endpoint

4. **`Access-Control-Allow-Headers: Content-Type`**
   - Allows frontend to send `Content-Type` header in their request
   - Example: Frontend sends `'Content-Type': 'application/json'` to tell PHP the data format
   - Without this, browser blocks the request due to CORS security

**Frontend Example:**
```javascript
fetch('http://localhost/backend/public/addevent.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'  // ← Needs permission from PHP
    },
    body: JSON.stringify({ title: 'Event', date: '2025-12-01' })
})
```

---

### Q4: Reading JSON data from request (2025-11-25)
**Question:** What does `file_get_contents('php://input')` do and why is it needed?

**Code:**
```php
$data = json_decode(file_get_contents('php://input'), true);
```

**Explanation:**

**`file_get_contents('php://input')`**
- Reads the raw data from the HTTP request body
- `php://input` is a special stream (like a mailbox) where PHP stores incoming request data
- Normally `file_get_contents()` reads files, but here it reads from a stream

**`json_decode(..., true)`**
- Converts JSON string to PHP array
- `true` = return associative array (not object)

**Flow:**
1. Frontend sends POST: `fetch('addevent.php', { body: JSON.stringify({title: "Concert", ...}) })`
2. Web server receives request and passes to PHP
3. PHP automatically stores request body in `php://input` stream (as raw text)
4. `file_get_contents('php://input')` reads that raw text
5. `json_decode()` converts JSON string to usable PHP array

**Why read it if PHP already stored it?**
- PHP only **stores** the raw text, doesn't process it
- You must explicitly **read** and **decode** it to use the data

**Analogy:** 
- PHP = Mailman who delivers letter to your mailbox (`php://input`)
- You still need to: open mailbox (`file_get_contents`) and read the letter (`json_decode`)

**Example transformation:**
```
Raw: '{"title":"Concert","date":"2025-12-01"}'
↓
Array: ['title' => 'Concert', 'date' => '2025-12-01']
```

---

### Q5: Null coalescing operator (??) (2025-11-25)
**Question:** What does `??` do in variable assignments?

**Code:**
```php
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$event_date = $data['date'] ?? '';
```

**Explanation:**

The `??` is the **null coalescing operator** - provides a safe fallback value.

**How it works:**
- Try to get `$data['title']`
- If it doesn't exist or is null → use `''` (empty string) instead
- Prevents errors when data is missing

**Example:**

Frontend sends incomplete data:
```json
{"title": "Concert", "description": "Music show"}
// Missing 'date' field
```

Result:
- `$title = "Concert"` ✓
- `$description = "Music show"` ✓
- `$event_date = ""` (safely defaults to empty string)

**Without `??` (unsafe):**
```php
$title = $data['title']; // ERROR if 'title' doesn't exist!
```

**With `??` (safe):**
```php
$title = $data['title'] ?? ''; // Gets empty string if missing
```

---

### Q6: How echo sends data to frontend (2025-11-25)
**Question:** How does `echo` know where to send the response? How does it reach the frontend?

**Code:**
```php
echo json_encode($result);
```

**Explanation:**

`echo` outputs data back to whoever called the script. The routing is automatic via HTTP protocol.

**Flow:**
1. Frontend makes request → Opens HTTP connection to `addevent.php`
2. PHP script runs → Processes the request
3. `echo` outputs data → Writes to the HTTP response body
4. Web server sends response → Routes it back through the same connection
5. Frontend receives it → Gets the JSON response

**You don't specify "who" to send to because:**
- HTTP connection is already established when script runs
- `echo` just writes to the response stream
- Web server (Apache/WAMP) automatically routes it back to the requester

**Analogy:**
- Phone call: Someone calls you (request)
- You answer (script runs)
- You speak (echo)
- They hear you automatically (response received)
- No need to say "send to caller #1" - phone system knows!

**Frontend receives:**
```javascript
fetch('addevent.php', {...})
  .then(response => response.json())
  .then(data => {
    console.log(data); // {"success": true, "message": "Event created"}
  })
```

**In short:** HTTP protocol + web server handle all routing automatically. `echo` just outputs to the response stream.

---

### Q7: fetchAll with PDO::FETCH_ASSOC (2025-11-25)
**Question:** How does the SELECT query with fetchAll work?

**Code:**
```php
$stmt = $pdo->query("SELECT * FROM participants ORDER BY participant_id DESC");
return $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Explanation:**

**Line 1:** Execute query
- `SELECT *` - Get all columns
- `FROM participants` - From the participants table
- `ORDER BY participant_id DESC` - Sort by ID, newest first (descending order)
- Returns statement object with results

**Line 2:** Fetch and return data
- `fetchAll()` - Get all rows at once (returns array of arrays)
- `PDO::FETCH_ASSOC` - Each row as associative array with column names as keys
- `return` - Send array back to caller

**Result example:**
```php
[
    ['participant_id' => 3, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['participant_id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
    ['participant_id' => 1, 'name' => 'Charlie', 'email' => 'charlie@example.com']
]
```

---

### Q8: $_SERVER superglobal array (2025-11-25)
**Question:** What is `$_SERVER` and where does it come from?

**Code:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') { ... }
```

**Explanation:**

`$_SERVER` is a **special built-in PHP array** (superglobal) containing request and server information.

**Where does it come from?**
- Automatically created by PHP when a request is received
- Populated by the web server (Apache/WAMP) and PHP
- Always available - you don't need to create or import it

**What's inside?**
- Request details: HTTP method, URL, headers
- Server info: IP addresses, paths, server software
- Client info: User agent, referrer, etc.

**`$_SERVER['REQUEST_METHOD']`**
- Contains the HTTP method used for the request
- Values: `'GET'`, `'POST'`, `'PUT'`, `'DELETE'`, etc.

**Example flow:**
```javascript
// Frontend
fetch('addevent.php', { method: 'POST', ... })
```
↓
```php
// PHP automatically sets
$_SERVER['REQUEST_METHOD'] = 'POST'
```

**Other useful `$_SERVER` values:**
- `$_SERVER['REQUEST_URI']` - Requested URL path
- `$_SERVER['REMOTE_ADDR']` - Client's IP address
- `$_SERVER['HTTP_USER_AGENT']` - Browser information
- `$_SERVER['HTTP_HOST']` - Domain name

**In short:** `$_SERVER` is a built-in PHP array with request/server data, automatically created for every request.

---

### Q9: Participants endpoint structure (2025-11-25)
**Question:** Does `header()` make an endpoint? What do the two `require_once` lines do, and why not call add-participant here?

**Context (list endpoint):**
```php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';   // provides $pdo (DB connection)
require_once __DIR__ . '/../src/participants.php'; // provides get/create functions

echo json_encode(getParticipants($pdo));
```

**Explanation:**
- File = Endpoint: The PHP file itself is the endpoint. `header()` only sets response type (JSON); it doesn't create the endpoint.
- `require_once db.php` = Includes code that creates `$pdo` so DB calls work.
- `require_once participants.php` = Includes functions like `getParticipants()` and `createParticipant()`.
- `echo json_encode(getParticipants($pdo))` = Runs the list function and sends the result as JSON to the client.

**Why not call add-participant here?**
- Separation of concerns: listing and adding are different actions.
- Typically: `participants.php` handles GET (list), while `addparticipant.php` handles POST (create).
- Keeping separate endpoints makes behavior clear and routing simple on the frontend.

---

### Q10: SQL JOIN to get event registrations (2025-11-25)
**Question:** How does the JOIN query work to get participants for an event?

**Code:**
```php
$stmt = $pdo->prepare("
    SELECT p.name, p.email 
    FROM registrations r
    JOIN participants p ON r.participant_id = p.participant_id
    WHERE r.event_id = ?
");
```

**Explanation:**

**Line by line:**
1. `$pdo->prepare(...)` - Creates prepared statement with placeholder `?`
2. `SELECT p.name, p.email` - Get only participant name and email (not all columns)
3. `FROM registrations r` - Start from registrations table (alias `r`). Each row links event to participant.
4. `JOIN participants p ON r.participant_id = p.participant_id` - Connect each registration to matching participant row to get their details
5. `WHERE r.event_id = ?` - Filter: only registrations for the specific event (placeholder replaced on execute)

**What it returns:** All participants (name + email) registered for one specific event.

**Execute:**
```php
$stmt->execute([$event_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Example result:**
```php
[
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com']
]
```

**In short:** Find every participant linked (via registrations table) to one event ID.

---

### Q11: Validating URL parameters with isset & ctype_digit (2025-11-25)
**Question:** What does the condition check in the registrations endpoint?

**Code:**
```php
if (isset($_GET['event_id']) && ctype_digit($_GET['event_id'])) {
    echo json_encode(getRegistrationsByEvent($pdo, (int)$_GET['event_id']));
} else {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
}
```

**Explanation:**

**The condition checks TWO things:**

1. **`isset($_GET['event_id'])`**
   - Checks if `event_id` parameter exists in URL
   - Example: `registrations.php?event_id=5`
   - Returns `true` if parameter exists, `false` if missing

2. **`ctype_digit($_GET['event_id'])`**
   - Validates value contains ONLY digits (0-9)
   - Returns `true` for `'123'`, `false` for `'abc'`, `'-5'`, or `'12.5'`
   - Prevents SQL injection and invalid data

**Both must be true (`&&`) to proceed.**

**If condition passes:**
- `(int)$_GET['event_id']` - Convert string to integer (`'5'` → `5`)
- Call function to get registrations for that event
- Return JSON with results

**If condition fails:**
- Return error message: `{"success": false, "message": "Event ID required"}`

**Examples:**

✅ Valid: `registrations.php?event_id=5`
- `isset()` = true, `ctype_digit('5')` = true → Returns registrations

❌ Invalid: `registrations.php` (no parameter)
- `isset()` = false → Returns error

❌ Invalid: `registrations.php?event_id=abc`
- `isset()` = true, `ctype_digit('abc')` = false → Returns error

❌ Invalid: `registrations.php?event_id=-5`
- `isset()` = true, `ctype_digit('-5')` = false → Returns error

**Endpoint purpose:** Get list of all participants registered for a specific event (via `event_id` URL parameter).

**Frontend example:**
```javascript
fetch('registrations.php?event_id=5')
  .then(response => response.json())
  .then(data => console.log(data));
// Returns: [{"name": "Alice", "email": "alice@example.com"}, ...]
```

---

### Q12: How $stmt gets execute() and fetch() methods (2025-11-26)
**Question:** How does `$stmt` have methods like `execute()` and `fetch()` just by setting it equal to `prepare()`?

**Code:**
```php
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();
```

**Explanation:**

`$stmt` gets those methods because `prepare()` returns a **PDOStatement object**, which has those methods built into it.

**Step-by-step:**

1. **`$pdo->prepare("SELECT ...")`**
   - Returns a **PDOStatement object**
   - This is an instance of PHP's built-in `PDOStatement` class

2. **`$stmt = ...`**
   - Stores that PDOStatement object in the variable
   - Now `$stmt` IS a PDOStatement object

3. **PDOStatement class has built-in methods:**
   - `execute()` - Run the query with parameter values
   - `fetch()` - Get one row from results
   - `fetchAll()` - Get all rows from results
   - `rowCount()` - Count affected rows
   - And more...

**Object types:**
- `$pdo` = PDO object (database connection)
  - Methods: `prepare()`, `query()`, `beginTransaction()`
- `$stmt` = PDOStatement object (prepared query)
  - Methods: `execute()`, `fetch()`, `fetchAll()`, `rowCount()`

**Analogy:**
```php
$pizza = pizzaShop->order("pepperoni");  // Returns Pizza object
$pizza->slice();                          // Pizza object has slice() method
$pizza->eat();                            // Pizza object has eat() method
```

The methods aren't added by you - they're already part of the PDOStatement class that PHP provides!

**Why it works:**
- `prepare()` creates a statement object that "remembers" your SQL query
- `execute()` fills in placeholders and runs the query
- `fetch()` retrieves results from that same statement object
- The statement object holds both the query and the results

**In short:** Setting `$stmt = $pdo->prepare()` gives you a PDOStatement object, which comes with execute(), fetch(), and other methods pre-built by PHP.

---

### Q13: Understanding $_SESSION and session management (2025-11-26)
**Question:** What is `$_SESSION`? Where does it start and what does it contain?

**Code:**
```php
session_start();

function loginAdmin($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        return ['success' => true, 'message' => 'Login successful'];
    }
    return ['success' => false, 'message' => 'Invalid credentials'];
}
```

**Explanation:**

**What is `$_SESSION`?**
- A special PHP array (superglobal) that stores data **across multiple page requests**
- Keeps user data "alive" as they navigate your site
- Unique for each visitor to your website

**Where does it start?**
```php
session_start();  // Must be called before using $_SESSION
```
- Creates or resumes a session for the current user
- PHP generates a unique session ID and stores it in a cookie on the user's browser
- Must be called at the top of any file that uses `$_SESSION`

**What does it contain?**
- Initially: empty array `[]`
- After you store data: whatever you put in it
- Data persists across page requests until session ends

**How it works:**

1. **First page load (before login):**
```php
session_start();
// $_SESSION = []  (empty)
```

2. **After successful login:**
```php
$_SESSION['admin_id'] = $admin['admin_id'];
// $_SESSION = ['admin_id' => 5]
```

3. **Next page request (different PHP file):**
```php
session_start();  // Resumes the same session
// $_SESSION still has ['admin_id' => 5] ✓
```

4. **After logout:**
```php
session_destroy();
// $_SESSION = []  (cleared)
```

**How sessions persist:**
- PHP stores session data in a file on the server
- User's browser gets a cookie with session ID (e.g., `PHPSESSID=abc123xyz`)
- Each request sends that cookie → PHP loads matching session data → `$_SESSION` is populated

**Example: `$admin` vs `$_SESSION`**

`$admin` (from `fetch()`):
```php
$admin = [
    'admin_id' => 5,
    'username' => 'sarah_admin',
    'password' => '$2y$10$hashedPassword...'
];
```

`$_SESSION` (after storing):
```php
$_SESSION = [
    'admin_id' => 5  // Only store the ID, not password!
];
```

**Timeline example:**

**Request 1 - Login page (login.php):**
```php
session_start();
$_SESSION['admin_id'] = 5;
// Server: Creates session file with data
// Browser: Gets cookie PHPSESSID=abc123
```

**Request 2 - Events page (events.php):**
```php
session_start();
// Browser sends: Cookie with PHPSESSID=abc123
// PHP loads session file
// $_SESSION = ['admin_id' => 5]  ✓ Still there!

if (isset($_SESSION['admin_id'])) {
    echo "You're logged in!";
}
```

**Request 3 - Logout (logout.php):**
```php
session_start();
session_destroy();
// Deletes session file
// $_SESSION = []  ✓ Gone!
```

**In your authentication system:**
- **Login** stores admin ID in session → User is "logged in"
- **`requireAdmin()`** checks if session has admin ID → Verifies they're logged in
- **Logout** destroys session → User is "logged out"

---

## CORS and Parameter Handling Issue (2025-11-26)

### Problem: 400 Bad Request when Deleting Participants
When trying to delete a participant, the frontend was receiving a 400 Bad Request error. The issue was caused by a mismatch in parameter naming between the frontend and backend.

### Root Causes:
1. **CORS Preflight**: The browser was sending an OPTIONS request first, which wasn't being handled properly
2. **Parameter Naming**: The frontend was sending `participant_id` but the backend was expecting `id`
3. **Error Handling**: Insufficient error reporting made it difficult to diagnose the issue

### Solution:
1. **Proper CORS Headers**: Added comprehensive CORS headers to handle preflight requests
   ```php
   header('Access-Control-Allow-Origin: *');
   header('Access-Control-Allow-Methods: POST, OPTIONS');
   header('Access-Control-Allow-Headers: Content-Type');
   ```
2. **Parameter Flexibility**: Updated the backend to handle multiple parameter names
   ```php
   $possibleKeys = ['participant_id', 'id', 'participantId'];
   foreach ($possibleKeys as $key) {
       if (isset($input[$key]) && is_numeric($input[$key])) {
           $participantId = (int)$input[$key];
           break;
       }
   }
   ```
3. **Enhanced Logging**: Added detailed error logging to help with debugging
   ```php
   $rawInput = file_get_contents('php://input');
   file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n", FILE_APPEND);
   ```

### Key Learnings:
1. Always handle CORS preflight requests (OPTIONS) properly
2. Be consistent with parameter naming between frontend and backend
3. Implement detailed logging for API endpoints to simplify debugging
4. Validate and sanitize all input parameters
5. Use proper error handling and meaningful error messages

**Security note:** Never store sensitive data like passwords in `$_SESSION` - only store the user ID to identify them.

---

### Q14: Why session_start() before session_destroy() (2025-11-26)
**Question:** In the logout function, why do we need `session_start()` before `session_destroy()`? Why not destroy the session directly?

**Code:**
```php
function logoutAdmin() {
    session_start();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out'];
}
```

**Explanation:**

You need `session_start()` before `session_destroy()` because **you can't destroy something that isn't loaded yet**.

**Why `session_start()` is required:**

`session_destroy()` needs an active session to destroy:
- When logout is called, PHP doesn't automatically know which session to destroy
- `session_start()` loads the session data from the server (using the session ID from the cookie)
- Only then can `session_destroy()` delete that session file

**Without `session_start()` (won't work):**
```php
function logoutAdmin() {
    session_destroy();  // ❌ ERROR!
}
// PHP Warning: session_destroy(): Trying to destroy uninitialized session
```

**With `session_start()` (works correctly):**
```php
function logoutAdmin() {
    session_start();     // ✓ Load session (identify which one)
    session_destroy();   // ✓ Now destroy it
}
```

**Step-by-step flow:**

1. **User clicks logout**
2. **`session_start()` runs:**
   - Browser sends cookie: `PHPSESSID=abc123`
   - PHP finds session file matching `abc123` on server
   - Loads that session into memory
3. **`session_destroy()` runs:**
   - Deletes that specific session file from server
   - Clears `$_SESSION` array
   - User is now logged out

**Analogy:**
- Session = Locker at the gym
- `session_start()` = Open your locker (using your key/ID)
- `session_destroy()` = Empty the locker and remove it
- **You can't empty a locker without opening it first!**

**In short:** `session_start()` tells PHP **which** session to destroy (by loading it first using the cookie), then `session_destroy()` deletes it from the server.

---

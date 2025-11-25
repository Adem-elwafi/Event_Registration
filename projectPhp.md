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

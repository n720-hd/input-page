<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IndiHome Dashboard - Input Management</title>
  <link rel="stylesheet" href="/css/style.css" />
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>IndiHome Number Input</h1>
      <p>Input your IndiHome subscription numbers</p>
    </div>
    <div class="logos-in-card">
      <div class="logo-left">
        <img src="/img/prime_video_logo.png" alt="Prime Video">
      </div>
      <div class="logo-right">
        <img src="/img/66e64187a41d1-Telkomsel%20(1).png" alt="Telkomsel">
      </div>
    </div>

    <div class="input-card">
      <form id="indihomeForm">
        <div class="form-group">
          <label for="subscriptionNumber" class="form-label">Subscription Number</label>
          <input
            type="text"
            id="subscriptionNumber"
            name="subscriptionNumber"
            class="form-input"
            placeholder="Enter your IndiHome subscription number"
            required
          />
        </div>
        <button type="submit" class="submit-btn">Submit</button>
        <div id="responseMsg" class="response-msg">
          <div class="response-msg-content"></div>
        </div>
      </form>
    </div>
  </div>

  <script src="/js/script.js"></script>
</body>
</html>
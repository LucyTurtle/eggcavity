<!DOCTYPE html>
<html>
  <head>
    <title>EggCavity</title>
    <meta charset="UTF-8">
    <meta name="description" content="Free Web tutorials">
    <meta name="keywords" content="EggCav,EggCavity,Fan Site">
    <meta name="author" content="John Doe">
    <link rel="stylesheet" type="text/css" href="stylesheet.css">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Calligraffitti">
    <?php include 'viewer.php';?>
    <style>
      h1 {
        font-family: 'Calligraffitti', sans-serif;
      }
    </style>
    </style>
  </head>

  <body>
    <header>
    </header>
    <nav>
      <ul class="nav">
        <li><a class="blue" href="/">Home</a></li>
        <li><a class="purple" href="/viewer">Viewer</a></li>
        <li class="dropdown">
          <a href="/index" class="dropbtn orange">Indexes</a>
          <div class="dropdown-content">
            <a href="/index/creature">Creature</a>
            <a href="/index/travel">Travel</a>
            <a href="/index/trinket">Trinket</a>
            <a href="/index/item">Item</a>
          </div>
        </li>
        <li><a class="green" href="/suggestions">Suggestions</a></li>
        <li><a class="yellow" href="/calculator">Calculator</a></li>
      </ul>
    </nav>

    <div class="main">
      <div class="sidebar">
        <p>Profile info if logged in, login screen if not. Creature of the month.
      </div>

      <code>
  <html>

      <?php
        // Info to connect to the Travels database
        $servername = "eggcavity.com";
        $username = "lbowe_elbow";
        $password = "kAr3nn4!19";
        $dbname = "EggcavityTravelIndex";

        // To connect to the database please
        $conn = new mysqli($servername, $username, $password, $dbname);

        // If unable to connect to the database display this error
        if ($conn->connect_error) {
          echo "Connection error";
          die("Connection failed: " . $conn->connect_error);
        }

        // Retrieve data from the database
        $sql = "SELECT Name, Picture FROM Travels ORDER BY Name ASC";
        $result = $conn->query($sql);

        // Display all of the data from the database
        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
            $counter++;
            echo '<option value="' . $row["Picture"] . '">' . $row["Name"] . "</option>";
          }
        } else {
          echo "0 results";
        }

        // Close the connection to the database
        $conn->close();
      ?>
    </select>

    <br><br>
    <strong>Trinket Travel (Egg Stage = Stage 1):</strong><br>
    <select id="trinketselect" style="font-family: 'Source Sans Pro', Trebuchet MS, sans-serif;
      font-weight: 700; color: #000; padding: 5px 15px 5px 15px; text-decoration: none;
      border-radius: 5px;background-color: #d9e6c7;border: 0;-webkit-appearance:none;
      text-align: center;">
      <?php
        // Info to connect to the Travels database
        $servername = "eggcavity.com";
        $username = "lbowe_elbow";
        $password = "kAr3nn4!19";
        $dbname = "EggcavityTravelIndex";

        // To connect to the database please
        $conn = new mysqli($servername, $username, $password, $dbname);

        // If unable to connect to the database display this error
        if ($conn->connect_error) {
          echo "Connection error";
          die("Connection failed: " . $conn->connect_error);
        }

        // Retrieve data from the database
        $sql = "SELECT Name, Creature, Stage, Picture FROM TrinketTravels ORDER BY Name ASC";
        $result = $conn->query($sql);

        // Display all of the data from the database
        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
            echo '<option value="' . $row["Picture"] . '">' . $row["Creature"] .
              ' (Stage ' . $row["Stage"] . '): ' . $row["Name"] . "</option>";
          }
        } else {
          echo "0 results";
        }

        // Close the connection to the database
        $conn->close();
      ?>
    </select>

    <br><br>
    <strong>Creature: </strong><br>
    <select id="creatureselect" style="font-family: 'Source Sans Pro', Trebuchet MS, sans-serif;
      font-weight: 700; color: #000; padding: 5px 15px 5px 15px; text-decoration: none;
      border-radius: 5px;background-color: #d9e6c7;border: 0;-webkit-appearance:none;
      text-align: center; margin-bottom: 5px;">
      <?php
        // Info to connect to the Travels database
        $servername = "eggcavity.com";
        $username = "lbowe_elbow";
        $password = "kAr3nn4!19";
        $dbname = "EggcavityTravelIndex";

        // To connect to the database please
        $conn = new mysqli($servername, $username, $password, $dbname);

        // If unable to connect to the database display this error
        if ($conn->connect_error) {
          echo "Connection error";
          die("Connection failed: " . $conn->connect_error);
        }

        // Retrieve data from the database
        $sql = "SELECT Name, Stage1, Stage2, Stage3, Stage4 FROM Creatures ORDER BY Name ASC";
        $result = $conn->query($sql);

        // Display all of the data from the database
        if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
            echo '<option value="' . $row["Stage1"] . ',' . $row["Stage2"] . ',' . $row["Stage3"] .
              ',' . $row["Stage4"] . '">' . $row["Name"] . "</option>";
          }
        // If there is no data found then show this error
        } else {
          echo "0 results";
        }

        // Close the connection to the database
        $conn->close();
      ?>
    </select>

    <script type="text/javascript">
      function changeBackground() {
          var img1 = document.getElementById("backgroundimage1");
          var img2 = document.getElementById("backgroundimage2");
          var img3 = document.getElementById("backgroundimage3");
          var img4 = document.getElementById("backgroundimage4");
          img1.src = this.value;
          img2.src = this.value;
          img3.src = this.value;
          img4.src = this.value;
          return false;
      }
      document.getElementById("travelselect").onchange = changeBackground;
      document.getElementById("trinketselect").onchange = changeBackground;
    </script>

    <script type="text/javascript">
      function changeCreature() {
          var img1 = document.getElementById("creatureimage1");
          var img2 = document.getElementById("creatureimage2");
          var img3 = document.getElementById("creatureimage3");
          var img4 = document.getElementById("creatureimage4");
          var stages = this.value.split(',')
          img1.src = stages[0];
          img2.src = stages[1];
          img3.src = stages[2];
          img4.src = stages[3];
          return false;
      }
      document.getElementById("creatureselect").onchange = changeCreature;
    </script></center></html></code><br>

    If the images don't load right away, give them a couple seconds. <br>
    Especially the images that are not available in the EggCave archives!<br><br>

    If you encounter any problems while using the creature viewer, <br>
    please use the contact us form, and let us know what is going on! (See side bar)<br><br>

    <strong>Help Egg Cavity collect the stages of the Thief Shop creatures.</strong><br>
    If you know of a stage <em>without</em> a travel please use the contact us form, <br>
    and let us know where to find it! (See side bar)<br><br>

    <strong>These are the creature images we need (With stage 1 being egg stage):</strong><br>
    <br><img src="http://eggcave.com/egg/1777731.png" height="1" width="1" style="opacity: 0.0;"></img><br>


      </div>
    </div>
  </body>
</html>

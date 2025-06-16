<!-- Excel Modal -->
<div id="excelModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background-color:rgba(0,0,0,0.5); z-index:1000;">
  <div style="background:white; width:400px; margin:80px auto; padding:20px; border-radius:10px; position:relative;">
    <h3>Download Expense Excel</h3>
    <form action="download_excel.php" method="POST">
      <label>Select Vertical:</label><br>
      <select name="vertical_id" id="verticalSelect" onchange="fetchProcesses(this.value)" required>
        <option value="">Select</option>
        <?php foreach ($verticals as $vertical) {
          echo "<option value='{$vertical['vertical_id']}'>{$vertical['vertical_name']}</option>";
        } ?>
      </select><br><br>

      <label>Select Processes:</label><br>
      <div id="processCheckboxes">
        <!-- JS will populate this -->
      </div><br>

      <label>Start Date:</label><br>
      <input type="date" name="start_date" required><br><br>

      <label>End Date:</label><br>
      <input type="date" name="end_date" required><br><br>

      <button type="submit">Download Excel</button>
      <button type="button" onclick="document.getElementById('excelModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<script>
  function fetchProcesses(verticalId) {
    if (!verticalId) return;
    fetch('fetch_processes.php?vertical_id=' + verticalId)
      .then(res => res.json())
      .then(data => {
        const box = document.getElementById('processCheckboxes');
        box.innerHTML = '';
        data.forEach(process => {
          box.innerHTML += `<input type="checkbox" name="process_ids[]" value="${process.process_id}"> ${process.process_name}<br>`;
        });
      });
  }
</script>

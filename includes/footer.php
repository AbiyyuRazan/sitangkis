<?php // includes/footer.php ?>
<footer class="footer">
  <div class="footer-brand">
    <div class="logo-icon" style="font-size:1.1rem">🏛️</div>
    <strong><?= APP_NAME ?></strong> — <?= APP_DESC ?>
  </div>
  <p style="margin-top:6px">© <?= date('Y') ?> Pemerintah Desa. Kelompok 6 — 4A Informatika UNSIKA Karawang.</p>
</footer>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Yakin?'))e.preventDefault();});
  });
});
function makeBarChart(id,labels,datasets){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'bar',data:{labels,datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:v=>'Rp '+(v/1e6).toFixed(0)+'jt'},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
}
function makePieChart(id,labels,data,colors){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'doughnut',data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
}
function makeLineChart(id,labels,data){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'line',data:{labels,datasets:[{label:'Realisasi (jt)',data,borderColor:'#1a3a6b',backgroundColor:'rgba(26,58,107,.1)',borderWidth:2.5,pointBackgroundColor:'#1a3a6b',tension:.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:v=>v+'jt'},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
}
</script>
</body>
</html>

  </div><!-- /pb -->
</div><!-- /ac -->
</div><!-- /aw -->
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Yakin hapus?'))e.preventDefault();});
  });
});
</script>
</body>
</html>

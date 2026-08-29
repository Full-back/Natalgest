    </div>
</main>
<?php if (isset($active) && $active === 'accueil'): ?>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($active) && $active === 'accueil'): ?>
<script>
  
  let currentSlide = 0;
  const slides = document.querySelectorAll('.accueil-bg-slide');
  
  function nextSlide() {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
  }
  
  setInterval(nextSlide, 6000); // Change toutes les 6 secondes
  

  window.addEventListener('scroll', function() {
    const cards = document.querySelectorAll('.card-materna-hero');
    const scrollAmount = window.scrollY;
    
    cards.forEach((card, index) => {
      const offset = scrollAmount * 0.3;
      const opacity = 0.12 + (Math.min(scrollAmount, 200) / 200) * 0.2;
      
      card.style.transform = `translateY(${-offset}px)`;
      card.style.background = `rgba(255, 255, 255, ${opacity})`;
    });
  });
</script>
<?php endif; ?>
</body>
</html>

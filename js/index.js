(function() {
        const slidesWrapper = document.getElementById('slidesWrapper');
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const sliderContainer = document.querySelector('.slider-container');

        let currentIndex = 0;
        const totalSlides = slides.length;
        let autoPlayInterval = null;
        const INTERVAL_TIME = 4000;

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            else if (index >= totalSlides) index = 0;
            currentIndex = index;
            const offset = -currentIndex * 100;
            slidesWrapper.style.transform = 'translateX(' + offset + '%)';

            dots.forEach(function(dot, i) {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        function nextSlide() { goToSlide(currentIndex + 1); }
        function prevSlide() { goToSlide(currentIndex - 1); }

        function startAutoPlay() {
            if (autoPlayInterval) clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(nextSlide, INTERVAL_TIME);
        }

        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
                autoPlayInterval = null;
            }
        }

        function restartAutoPlay() {
            stopAutoPlay();
            startAutoPlay();
        }

        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            nextSlide();
            restartAutoPlay();
        });

        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            prevSlide();
            restartAutoPlay();
        });

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'), 10);
                if (!isNaN(index) && index !== currentIndex) {
                    goToSlide(index);
                    restartAutoPlay();
                }
            });
        });

        sliderContainer.addEventListener('mouseenter', stopAutoPlay);
        sliderContainer.addEventListener('mouseleave', startAutoPlay);

        goToSlide(0);
        startAutoPlay();

        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
    })();
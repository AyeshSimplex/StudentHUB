/**
 * StudentHub — Main JavaScript
 * 
 * Features:
 * 1. Dynamic Filtering (search, category, sort)
 * 2. Image Slider (Bootstrap carousel)
 * 3. Form Validation (registration, login, contact, projects)
 * 4. Smooth Scrolling
 * 5. Event Handling (hover, click, delete confirmation)
 * 6. Custom Animations (fade-in, slide-up, counters)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===================================
    // 1. Navbar Scroll Effect
    // ===================================
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ===================================
    // 1.1 Hero Carousel Setup
    // ===================================
    const heroCarousel = document.getElementById('heroCarousel');
    if (heroCarousel && typeof bootstrap !== 'undefined') {
        bootstrap.Carousel.getOrCreateInstance(heroCarousel, {
            interval: 4000,
            ride: 'carousel',
            pause: 'hover',
            wrap: true
        });
    }

    // ===================================
    // 2. Animated Counters
    // ===================================
    const counters = document.querySelectorAll('.stat-counter');
    if (counters.length > 0) {
        const animateCounter = function (counter) {
            const target = parseInt(counter.getAttribute('data-target'));
            const suffix = counter.getAttribute('data-suffix') || '';
            const duration = 2000; // 2 seconds
            const startTime = performance.now();

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease-out cubic
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(easeOut * target);

                counter.textContent = current + suffix;

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    counter.textContent = target + suffix;
                }
            }

            requestAnimationFrame(update);
        };

        // Use Intersection Observer to trigger counters when visible
        const counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(function (counter) {
            counterObserver.observe(counter);
        });
    }

    // ===================================
    // 3. Scroll Animations (Fade-in / Slide-up)
    // ===================================
    const animatedElements = document.querySelectorAll('.fade-in, .slide-up');
    if (animatedElements.length > 0) {
        const animationObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    animationObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        animatedElements.forEach(function (el) {
            animationObserver.observe(el);
        });
    }

    // ===================================
    // 4. Smooth Scrolling for Anchor Links
    // ===================================
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ===================================
    // 5. Dynamic Project Filtering
    // ===================================
    const searchInput = document.getElementById('projectSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    const projectCards = document.querySelectorAll('.project-filter-item');
    const projectCount = document.getElementById('projectCount');
    const noResults = document.getElementById('noResults');

    function filterProjects() {
        if (!searchInput && !categoryFilter) return;

        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedCategory = categoryFilter ? categoryFilter.value : 'all';
        let visibleCount = 0;
        let visibleCards = [];

        projectCards.forEach(function (card) {
            const title = (card.getAttribute('data-title') || '').toLowerCase();
            const description = (card.getAttribute('data-description') || '').toLowerCase();
            const tech = (card.getAttribute('data-technologies') || '').toLowerCase();
            const author = (card.getAttribute('data-author') || '').toLowerCase();
            const category = (card.getAttribute('data-category') || '').toLowerCase();

            const matchesSearch = !searchTerm ||
                title.includes(searchTerm) ||
                description.includes(searchTerm) ||
                tech.includes(searchTerm) ||
                author.includes(searchTerm);

            const matchesCategory = selectedCategory === 'all' ||
                category === selectedCategory.toLowerCase();

            if (matchesSearch && matchesCategory) {
                card.style.display = '';
                visibleCount++;
                visibleCards.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        // Sort visible cards
        if (sortFilter && visibleCards.length > 0) {
            const sortValue = sortFilter.value;
            const parent = projectCards[0].parentElement;

            visibleCards.sort(function (a, b) {
                if (sortValue === 'oldest') {
                    return (a.getAttribute('data-date') || '').localeCompare(b.getAttribute('data-date') || '');
                } else if (sortValue === 'az') {
                    return (a.getAttribute('data-title') || '').localeCompare(b.getAttribute('data-title') || '');
                } else {
                    // Newest first (default)
                    return (b.getAttribute('data-date') || '').localeCompare(a.getAttribute('data-date') || '');
                }
            });

            // Re-order DOM
            visibleCards.forEach(function (card) {
                parent.appendChild(card);
            });

            // Also move hidden cards to end
            projectCards.forEach(function (card) {
                if (card.style.display === 'none') {
                    parent.appendChild(card);
                }
            });
        }

        // Update count
        if (projectCount) {
            projectCount.innerHTML = 'Showing <strong>' + visibleCount + '</strong> project' + (visibleCount !== 1 ? 's' : '');
        }

        // Show/hide no results
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterProjects);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterProjects);
    }
    if (sortFilter) {
        sortFilter.addEventListener('change', filterProjects);
    }

    // Auto-apply filter if a category is pre-selected (e.g. from URL parameter)
    if (categoryFilter && categoryFilter.value !== 'all') {
        filterProjects();
    }

    // ===================================
    // 6. Form Validation
    // ===================================

    /**
     * Show error on a form field
     */
    function showError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        const errorEl = field.parentElement.querySelector('.form-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }
    }

    /**
     * Clear error on a form field
     */
    function clearError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        const errorEl = field.parentElement.querySelector('.form-error');
        if (errorEl) {
            errorEl.classList.remove('show');
        }
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // --- Registration Form ---
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            let isValid = true;

            const username = document.getElementById('username');
            const fullName = document.getElementById('full_name');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            // Username
            if (username && username.value.trim().length < 3) {
                showError(username, 'Username must be at least 3 characters.');
                isValid = false;
            } else if (username) {
                clearError(username);
            }

            // Full Name
            if (fullName && fullName.value.trim().length < 2) {
                showError(fullName, 'Please enter your full name.');
                isValid = false;
            } else if (fullName) {
                clearError(fullName);
            }

            // Email
            if (email && !isValidEmail(email.value.trim())) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            } else if (email) {
                clearError(email);
            }

            // Password
            if (password && password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters.');
                isValid = false;
            } else if (password) {
                clearError(password);
            }

            // Confirm Password
            if (confirmPassword && password && confirmPassword.value !== password.value) {
                showError(confirmPassword, 'Passwords do not match.');
                isValid = false;
            } else if (confirmPassword) {
                clearError(confirmPassword);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // --- Login Form ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            let isValid = true;

            const email = document.getElementById('email');
            const password = document.getElementById('password');

            if (email && !isValidEmail(email.value.trim())) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            } else if (email) {
                clearError(email);
            }

            if (password && password.value.length === 0) {
                showError(password, 'Please enter your password.');
                isValid = false;
            } else if (password) {
                clearError(password);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // --- Contact Form ---
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            let isValid = true;

            const name = document.getElementById('contact_name');
            const email = document.getElementById('contact_email');
            const subject = document.getElementById('contact_subject');
            const message = document.getElementById('contact_message');

            if (name && name.value.trim().length < 2) {
                showError(name, 'Please enter your name.');
                isValid = false;
            } else if (name) {
                clearError(name);
            }

            if (email && !isValidEmail(email.value.trim())) {
                showError(email, 'Please enter a valid email address.');
                isValid = false;
            } else if (email) {
                clearError(email);
            }

            if (subject && subject.value.trim().length < 3) {
                showError(subject, 'Please enter a subject.');
                isValid = false;
            } else if (subject) {
                clearError(subject);
            }

            if (message && message.value.trim().length < 10) {
                showError(message, 'Message must be at least 10 characters.');
                isValid = false;
            } else if (message) {
                clearError(message);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // --- Add/Edit Project Form ---
    const projectForm = document.getElementById('projectForm');
    if (projectForm) {
        projectForm.addEventListener('submit', function (e) {
            let isValid = true;

            const title = document.getElementById('project_title');
            const description = document.getElementById('project_description');
            const category = document.getElementById('project_category');
            const technologies = document.getElementById('project_technologies');

            if (title && title.value.trim().length < 3) {
                showError(title, 'Project title must be at least 3 characters.');
                isValid = false;
            } else if (title) {
                clearError(title);
            }

            if (description && description.value.trim().length < 20) {
                showError(description, 'Description must be at least 20 characters.');
                isValid = false;
            } else if (description) {
                clearError(description);
            }

            if (category && category.value === '') {
                showError(category, 'Please select a category.');
                isValid = false;
            } else if (category) {
                clearError(category);
            }

            if (technologies && technologies.value.trim().length < 2) {
                showError(technologies, 'Please enter the technologies used.');
                isValid = false;
            } else if (technologies) {
                clearError(technologies);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ===================================
    // 7. Delete Confirmation
    // ===================================
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    const deleteMsgButtons = document.querySelectorAll('.delete-msg-btn');
    deleteMsgButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ===================================
    // 8. Auto-dismiss Alerts
    // ===================================
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // ===================================
    // 9. Image Preview on Upload
    // ===================================
    const imageInput = document.getElementById('project_image');
    const imagePreview = document.getElementById('imagePreview');
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPG, PNG, or WEBP).');
                    this.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image file size must be under 5MB.');
                    this.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ===================================
    // 10. Real-time Form Field Validation
    // ===================================
    document.querySelectorAll('.validate-on-blur').forEach(function (field) {
        field.addEventListener('blur', function () {
            const value = this.value.trim();
            const type = this.getAttribute('data-validate');

            if (type === 'required' && value.length === 0) {
                showError(this, 'This field is required.');
            } else if (type === 'email' && !isValidEmail(value)) {
                showError(this, 'Please enter a valid email.');
            } else if (type === 'minlength') {
                const min = parseInt(this.getAttribute('data-min') || 3);
                if (value.length < min) {
                    showError(this, 'Must be at least ' + min + ' characters.');
                } else {
                    clearError(this);
                }
            } else {
                clearError(this);
            }
        });
    });

    // ===================================
    // 11. Password Visibility Toggle
    // ===================================
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                const icon = this.querySelector('i');
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    targetInput.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });
    });

    // ===================================
    // 12. Settings Forms Client-side Validation
    // ===================================
    const changeEmailForm = document.getElementById('changeEmailForm');
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', function (e) {
            const newEmail = document.getElementById('new_email').value.trim();
            const confirmEmail = document.getElementById('confirm_email').value.trim();
            const currPass = document.getElementById('email_current_password').value;

            if (!isValidEmail(newEmail)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return false;
            }
            if (newEmail.toLowerCase() !== confirmEmail.toLowerCase()) {
                alert('Confirmation email does not match.');
                e.preventDefault();
                return false;
            }
            if (!currPass) {
                alert('Please enter your current password to authorize this change.');
                e.preventDefault();
                return false;
            }
        });
    }

    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            const currPass = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (!currPass) {
                alert('Please enter your current password.');
                e.preventDefault();
                return false;
            }
            if (newPass.length < 6) {
                alert('New password must be at least 6 characters long.');
                e.preventDefault();
                return false;
            }
            if (newPass !== confirmPass) {
                alert('New password and confirmation do not match.');
                e.preventDefault();
                return false;
            }
        });
    }

});

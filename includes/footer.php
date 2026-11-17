</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col footer-brand">
            <a href="<?= url('index.php') ?>" class="logo logo-light">
                <span class="logo-mark"><i class="fa-solid fa-staff-snake"></i></span>
                <span class="logo-text">Care<strong>Point</strong></span>
            </a>
            <p>Compassionate, modern healthcare for the whole family. Book appointments online with our experienced specialists in minutes.</p>
            <div class="socials">
                <a href="<?= url('contact.php') ?>" aria-label="Contact us by email"><i class="fa-solid fa-envelope"></i></a>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', CLINIC_PHONE)) ?>" aria-label="Call us"><i class="fa-solid fa-phone"></i></a>
                <a href="<?= url('contact.php#map') ?>" aria-label="Find us"><i class="fa-solid fa-location-dot"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= url('about.php') ?>">About Us</a></li>
                <li><a href="<?= url('services.php') ?>">Medical Services</a></li>
                <li><a href="<?= url('doctors.php') ?>">Our Doctors</a></li>
                <li><a href="<?= url('book.php') ?>">Book an Appointment</a></li>
                <li><a href="<?= url('track.php') ?>">Track My Booking</a></li>
                <li><a href="<?= url('dashboard.php') ?>">Patient Dashboard</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Opening Hours</h4>
            <ul class="hours-list">
                <li><span>Monday – Friday</span><span>8:00 AM – 6:00 PM</span></li>
                <li><span>Saturday</span><span>9:00 AM – 2:00 PM</span></li>
                <li><span>Sunday</span><span>Closed</span></li>
                <li class="hl"><span>Emergency line</span><span>24 / 7</span></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact Us</h4>
            <ul class="contact-list">
                <li><i class="fa-solid fa-location-dot"></i><span><?= e(CLINIC_ADDRESS) ?></span></li>
                <li><i class="fa-solid fa-phone"></i><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', CLINIC_PHONE)) ?>"><?= e(CLINIC_PHONE) ?></a></li>
                <li><i class="fa-solid fa-envelope"></i><a href="mailto:<?= e(CLINIC_EMAIL) ?>"><?= e(CLINIC_EMAIL) ?></a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>&copy; <?= date('Y') ?> CarePoint Clinic · Developed by Tadas-Okonkwo Nnamdi (23/SCI01/149)</p>
            <p><a href="<?= url('admin/login.php') ?>"><i class="fa-solid fa-lock"></i> Staff / Admin Login</a></p>
        </div>
    </div>
</footer>

<button class="back-to-top" id="backToTop" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

<script src="<?= asset('js/main.js') ?>"></script>
<?php foreach (($vendorScripts ?? []) as $__vendor): ?>
<script src="<?= e($__vendor) ?>"></script>
<?php endforeach; ?>
<?php foreach (($pageScripts ?? []) as $__script): ?>
<script src="<?= asset('js/' . $__script) ?>"></script>
<?php endforeach; ?>
</body>
</html>

<!-- footer.php -->
<footer style="
    background: #8B4513;
    color: white;
    padding: 25px 20px;
    text-align: center;
    margin-top: 50px;
    border-top: 3px solid #A0522D;
    font-family: 'Roboto', sans-serif;
">
    <div style="
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    ">
        <p style="
            margin: 0 0 10px 0;
            font-size: 1rem;
            font-weight: 500;
        ">
            &copy; <?php echo date('Y'); ?> Classic Car Auctions
        </p>
        
        <div style="
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
            flex-wrap: wrap;
        ">
            <a href="index.php" style="
                color: #FFD700;
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                padding: 5px 10px;
                border-radius: 3px;
            " onmouseover="this.style.backgroundColor='rgba(255,215,0,0.1)';" 
               onmouseout="this.style.backgroundColor='transparent';">
                <i class="fas fa-home"></i> Home
            </a>
            
            <a href="search.php" style="
                color: #FFD700;
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                padding: 5px 10px;
                border-radius: 3px;
            " onmouseover="this.style.backgroundColor='rgba(255,215,0,0.1)';" 
               onmouseout="this.style.backgroundColor='transparent';">
                <i class="fas fa-search"></i> Search
            </a>
            
            <a href="#" style="
                color: #FFD700;
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                padding: 5px 10px;
                border-radius: 3px;
            " onmouseover="this.style.backgroundColor='rgba(255,215,0,0.1)';" 
               onmouseout="this.style.backgroundColor='transparent';">
                <i class="fas fa-info-circle"></i> About
            </a>
            
            <a href="#" style="
                color: #FFD700;
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                padding: 5px 10px;
                border-radius: 3px;
            " onmouseover="this.style.backgroundColor='rgba(255,215,0,0.1)';" 
               onmouseout="this.style.backgroundColor='transparent';">
                <i class="fas fa-envelope"></i> Contact
            </a>
        </div>
        
        <!-- SIMPLE CLICKABLE eSewa LOGO -->
        <div style="margin: 15px 0;">
            <a href="https://esewa.com.np" 
               target="_blank" 
               rel="noopener noreferrer"
               title="Click to visit eSewa official website">
                <img src="eseva_logo.png" 
                     alt="eSewa - Digital Wallet Nepal" 
                     style="
                         height: 30px;
                         width: auto;
                         vertical-align: middle;
                         margin: 0 10px;
                     ">
            </a>
        </div>
        
        <p style="
            font-size: 0.8rem;
            margin: 15px 0 0 0;
            opacity: 0.8;
            color: #FFE4B5;
        ">
            <i class="fas fa-shield-alt"></i> Secure Bidding System &bull; 
            <i class="fas fa-truck"></i> Worldwide Shipping &bull; 
            <i class="fas fa-headset"></i> 24/7 Support
        </p>
    </div>
</footer>

<!-- Simple JavaScript for interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll to top button
    const scrollToTop = document.createElement('button');
    scrollToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollToTop.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #8B4513;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    scrollToTop.addEventListener('mouseover', function() {
        this.style.background = '#A0522D';
        this.style.transform = 'translateY(-2px)';
    });
    
    scrollToTop.addEventListener('mouseout', function() {
        this.style.background = '#8B4513';
        this.style.transform = 'translateY(0)';
    });
    
    scrollToTop.addEventListener('click', function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
    
    document.body.appendChild(scrollToTop);
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollToTop.style.display = 'flex';
        } else {
            scrollToTop.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
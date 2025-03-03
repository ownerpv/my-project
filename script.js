document.addEventListener("DOMContentLoaded", function () {
    const contentSection = document.querySelector(".content");

    function checkScroll() {
        const scrollPosition = window.scrollY + window.innerHeight;
        const contentPosition = contentSection.offsetTop;

        if (scrollPosition > contentPosition + 100) {
            contentSection.classList.add("visible");
        }
    }

    window.addEventListener("scroll", checkScroll);
    checkScroll();
});

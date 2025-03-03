document.addEventListener("scroll", () => {
    let scrollPosition = window.scrollY;
    document.body.style.background = `rgb(${50 + scrollPosition / 5}, ${0 + scrollPosition / 10}, ${100 + scrollPosition / 5})`;
});

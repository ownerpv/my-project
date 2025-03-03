// افکت ظاهر شدن هنگام اسکرول
document.addEventListener("scroll", function () {
    let content = document.querySelector(".content");
    let position = content.getBoundingClientRect().top;
    let screenPosition = window.innerHeight / 1.3;

    if (position < screenPosition) {
        content.classList.add("show");
    }
});

// ایجاد ستاره‌های تصادفی در پس‌زمینه
function createStars(count) {
    const body = document.querySelector("body");

    for (let i = 0; i < count; i++) {
        let star = document.createElement("div");
        star.classList.add("star");
        let x = Math.random() * window.innerWidth;
        let y = Math.random() * window.innerHeight;
        let duration = Math.random() * 3 + 2;

        star.style.left = `${x}px`;
        star.style.top = `${y}px`;
        star.style.animationDuration = `${duration}s`;

        body.appendChild(star);
    }
}

createStars(100);

window.addEventListener('scroll', () => {
  const scrollY = window.scrollY;
  const hero = document.querySelector('.hero');
  const angle = 135 + (scrollY / window.innerHeight) * 45;
  hero.style.background = `linear-gradient(${angle}deg, #000, #4B0082)`;
});

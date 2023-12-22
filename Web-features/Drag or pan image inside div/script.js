// // script.js
// const image = document.getElementById('zoom-image');
// const imageContainer = document.querySelector('.image-container');

// let scale = 1;
// let translateX = 0;
// let translateY = 0;

// imageContainer.addEventListener('wheel', (event) => {
//   event.preventDefault();
//   console.log('deltaX', event.deltaX)
//   console.log('deltaY', event.deltaY);

//   // Change the scale based on mouse wheel scroll direction
//   scale += event.deltaY * -0.01;
//   scale = Math.min(Math.max(0.5, scale), 2);

//   // Calculate new image dimensions and position
//   const imgWidth = image.offsetWidth;
//   const imgHeight = image.offsetHeight;
//   const containerWidth = imageContainer.offsetWidth;
//   const containerHeight = imageContainer.offsetHeight;

//   translateX = (containerWidth - imgWidth * scale) / 2;
//   translateY = (containerHeight - imgHeight * scale) / 2;

//   // Apply transformations
//   image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
// });

var container = document.getElementById('container');
var image = document.getElementById('image');

var isDragging = false;
var offsetX = 0;
var offsetY = 0;

image.addEventListener('mousedown', function(e) {
  isDragging = true;
  offsetX = e.offsetX;
  offsetY = e.offsetY;
});

container.addEventListener('mousemove', function(e) {
  if (isDragging) {
    image.style.left = (e.pageX - offsetX) + 'px';
    image.style.top = (e.pageY - offsetY) + 'px';
  }
});

container.addEventListener('mouseup', function() {
  isDragging = false;
});

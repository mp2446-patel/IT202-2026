const status = document.querySelector("#status");
const button = document.querySelector("#changeStatus");

// Confirms both selectors found an element.
console.log({ status }, { button });
if (button) {
    button.addEventListener("click", function () {
        status.textContent = "Button clicked";
    });
}
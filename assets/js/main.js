document.addEventListener("DOMContentLoaded", () => {
  // Toggle sidebar on mobile
  const sidebarToggle = document.getElementById("sidebarToggle")
  const sidebar = document.querySelector(".bg-gray-800")

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("hidden")
    })
  }

  // Toggle user menu
  const userMenuButton = document.getElementById("userMenuButton")
  const userMenu = document.getElementById("userMenu")

  if (userMenuButton && userMenu) {
    userMenuButton.addEventListener("click", () => {
      userMenu.classList.toggle("hidden")
    })

    // Close user menu when clicking outside
    document.addEventListener("click", (event) => {
      if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
        userMenu.classList.add("hidden")
      }
    })
  }

  // Flash message auto-dismiss
  const flashMessage = document.querySelector('[role="alert"]')
  if (flashMessage) {
    setTimeout(() => {
      flashMessage.style.opacity = "0"
      flashMessage.style.transition = "opacity 1s"
      setTimeout(() => {
        flashMessage.remove()
      }, 1000)
    }, 5000)
  }
})


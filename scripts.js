const siteHeader = document.getElementById("siteHeader");
const navToggle = document.getElementById("navToggle");
const siteNav = document.getElementById("siteNav");
const navLinks = document.querySelectorAll(".nav-link");
const reveals = document.querySelectorAll(".reveal");
const metrics = document.querySelectorAll(".metric-number");
const sections = document.querySelectorAll("main section[id]");
const currentYear = document.getElementById("currentYear");
const heroVisual = document.getElementById("heroVisual");
const contactForm = document.getElementById("contactForm");
const formStatus = document.getElementById("formStatus");
const submitButton = document.getElementById("submitButton");

if (currentYear) {
    currentYear.textContent = new Date().getFullYear();
}

const closeNav = () => {
    siteNav.classList.remove("is-open");
    navToggle.classList.remove("is-active");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Open navigation");
    document.body.classList.remove("nav-open");
};

navToggle?.addEventListener("click", () => {
    const isOpen = siteNav.classList.toggle("is-open");
    navToggle.classList.toggle("is-active", isOpen);
    navToggle.setAttribute("aria-expanded", String(isOpen));
    navToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    document.body.classList.toggle("nav-open", isOpen);
});

navLinks.forEach((link) => {
    link.addEventListener("click", closeNav);
});

document.addEventListener("click", (event) => {
    if (!siteNav.contains(event.target) && !navToggle.contains(event.target)) {
        closeNav();
    }
});

const updateHeaderState = () => {
    siteHeader.classList.toggle("is-scrolled", window.scrollY > 24);
};

updateHeaderState();
window.addEventListener("scroll", updateHeaderState, { passive: true });

const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
        if (!entry.isIntersecting) {
            return;
        }

        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
    });
}, { threshold: 0.15, rootMargin: "0px 0px -60px 0px" });

reveals.forEach((element) => revealObserver.observe(element));

const animateMetric = (metric) => {
    const target = Number(metric.dataset.count || 0);
    const suffix = metric.dataset.suffix || "";
    const duration = 1200;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        metric.textContent = `${Math.round(target * eased)}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            metric.textContent = `${target}${suffix}`;
        }
    };

    requestAnimationFrame(step);
};

const metricObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
        if (!entry.isIntersecting) {
            return;
        }

        animateMetric(entry.target);
        observer.unobserve(entry.target);
    });
}, { threshold: 0.7 });

metrics.forEach((metric) => metricObserver.observe(metric));

const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (!entry.isIntersecting) {
            return;
        }

        const sectionId = entry.target.id;

        navLinks.forEach((link) => {
            const isActive = link.getAttribute("href") === `#${sectionId}`;
            link.classList.toggle("is-active", isActive);
        });
    });
}, {
    threshold: 0.55,
    rootMargin: "-10% 0px -35% 0px"
});

sections.forEach((section) => sectionObserver.observe(section));

if (window.matchMedia("(pointer: fine)").matches && heroVisual) {
    const visualPanel = heroVisual.querySelector(".visual-panel");

    heroVisual.addEventListener("mousemove", (event) => {
        const bounds = heroVisual.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - 0.5;
        const y = (event.clientY - bounds.top) / bounds.height - 0.5;

        visualPanel.style.setProperty("--pointer-x", (x * 8).toFixed(2));
        visualPanel.style.setProperty("--pointer-y", (y * 8).toFixed(2));
    });

    heroVisual.addEventListener("mouseleave", () => {
        visualPanel.style.setProperty("--pointer-x", "0");
        visualPanel.style.setProperty("--pointer-y", "0");
    });
}

contactForm?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(contactForm);
    formStatus.textContent = "Sending your message...";
    formStatus.classList.remove("is-success", "is-error");
    submitButton.disabled = true;

    try {
        const response = await fetch(contactForm.action, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        });

        const responseText = await response.text();
        let result;

        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error("The server returned an unexpected response.");
        }

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Something went wrong while sending your message.");
        }

        formStatus.textContent = result.message;
        formStatus.classList.add("is-success");
        contactForm.reset();
    } catch (error) {
        formStatus.textContent = error.message || "Unable to send the message right now.";
        formStatus.classList.add("is-error");
    } finally {
        submitButton.disabled = false;
    }
});

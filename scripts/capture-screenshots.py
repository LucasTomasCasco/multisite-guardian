"""Captures Network Admin screenshots from the local demo network.

Setup, once:
    python3 -m pip install playwright
    python3 -m playwright install chromium

Run after scripts/bootstrap-network.sh:
    python3 scripts/capture-screenshots.py
"""

import os
import pathlib

from playwright.sync_api import sync_playwright

URL = os.environ.get("GUARDIAN_URL", "http://localhost:8095")
PASSWORD = os.environ.get("GUARDIAN_ADMIN_PASSWORD", "guardian-local-only")
OUT = pathlib.Path(__file__).resolve().parent.parent / "docs" / "screenshots"

PAGES = {
    "overview": "multisite-guardian",
    "sites": "multisite-guardian-sites",
    "plugins": "multisite-guardian-plugins",
    "plugin-sites": "multisite-guardian-plugins&plugin=akismet/akismet.php",
    "themes": "multisite-guardian-themes",
    "matrix": "multisite-guardian-matrix",
    "audit": "multisite-guardian-audit",
}


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)

    with sync_playwright() as playwright:
        browser = playwright.chromium.launch()
        page = browser.new_page(viewport={"width": 1440, "height": 900})

        page.goto(f"{URL}/wp-login.php")
        page.fill("#user_login", "admin")
        page.fill("#user_pass", PASSWORD)
        page.click("#wp-submit")
        page.wait_for_load_state()

        # Rebuild the snapshot once, so the images show the network as it is now and not a cached copy.
        page.goto(f"{URL}/wp-admin/network/admin.php?page=multisite-guardian")
        page.click("text=Refresh data")
        page.wait_for_load_state()

        for name, slug in PAGES.items():
            page.goto(f"{URL}/wp-admin/network/admin.php?page={slug}")
            page.wait_for_load_state()
            page.screenshot(path=OUT / f"{name}.png", full_page=True)
            print(f"captured {name}.png")

        browser.close()


if __name__ == "__main__":
    main()

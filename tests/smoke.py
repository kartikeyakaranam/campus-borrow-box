"""End-to-end classroom smoke test. Run against a disposable local database."""
from http.cookiejar import CookieJar
from urllib.parse import urlencode
from urllib.request import build_opener, HTTPCookieProcessor, Request
from urllib.error import HTTPError
import re
import time
import base64
from datetime import datetime, timedelta

BASE = "http://127.0.0.1:8765/"


class Student:
    def __init__(self):
        self.browser = build_opener(HTTPCookieProcessor(CookieJar()))

    def get(self, path):
        try:
            with self.browser.open(BASE + path) as response:
                return response.geturl(), response.read().decode("utf-8")
        except HTTPError as response:
            if response.code == 404:
                return response.geturl(), response.read().decode("utf-8")
            raise

    def post(self, path, fields):
        _, page = self.get(path)
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', page)
        assert token, f"Missing CSRF token on {path}"
        data = urlencode({"csrf": token.group(1), **fields}).encode()
        with self.browser.open(BASE + "action.php", data=data) as response:
            return response.geturl(), response.read().decode("utf-8")

    def post_image(self, path, fields):
        _, page = self.get(path)
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)
        boundary = "BorrowBoxTestBoundary"
        parts = []
        for name, value in {"csrf": token, **fields}.items():
            parts.append(f"--{boundary}\r\nContent-Disposition: form-data; name=\"{name}\"\r\n\r\n{value}\r\n".encode())
        png = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg==")
        parts += [f'--{boundary}\r\nContent-Disposition: form-data; name="image"; filename="item.png"\r\nContent-Type: image/png\r\n\r\n'.encode(), png, f"\r\n--{boundary}--\r\n".encode()]
        request = Request(BASE + "action.php", data=b"".join(parts), headers={"Content-Type": f"multipart/form-data; boundary={boundary}"})
        with self.browser.open(request) as response:
            return response.geturl(), response.read().decode("utf-8")


def has(page, text):
    assert text in page, f"Expected {text!r} in page"


stamp = str(int(time.time()))
title = f"Scientific Calculator {stamp}"
pickup = (datetime.now() + timedelta(days=1)).replace(minute=0, second=0, microsecond=0)
return_time = pickup + timedelta(hours=4)
schedule = {"start_at": pickup.strftime("%Y-%m-%dT%H:%M"), "end_at": return_time.strftime("%Y-%m-%dT%H:%M")}
owner, borrower = Student(), Student()
_, home = owner.get("index.php")
has(home, "Borrow what you need")

url, page = owner.post("account.php", {
    "action": "register", "name": "Owner Test", "email": f"owner{stamp}@example.test", "password": "testpass123"
})
assert "dashboard.php" in url
has(page, "Welcome to Borrow Box")

url, page = borrower.post("account.php", {
    "action": "register", "name": "Borrower Test", "email": f"borrower{stamp}@example.test", "password": "testpass123"
})
assert "dashboard.php" in url

url, page = owner.post("add.php", {
    "action": "add_item", "title": title, "category": "Electronics",
    "description": "For first-year lab work.", "location": "Library entrance", "fee": "75.00"
})
item_id = re.search(r"item.php\?id=(\d+)", url).group(1)
has(page, title)
_, page = borrower.get(f"edit.php?id={item_id}")
has(page, "Only your available items can be edited")
_, page = borrower.post("dashboard.php", {
    "action": "edit_item", "item_id": item_id, "title": "Stolen title",
    "category": "Other", "description": "Unauthorized change", "location": "Elsewhere", "fee": "1.00"
})
has(page, "Only your available listing can be edited")
url, page = owner.post_image(f"edit.php?id={item_id}", {
    "action": "edit_item", "item_id": item_id, "title": title,
    "category": "Electronics", "description": "For first-year lab work. Includes a case.",
    "location": "Library entrance", "fee": "80.00"
})
has(page, "Listing saved")
has(page, "Includes a case")
image_path = re.search(r'src="(uploads/[a-f0-9]+\.png)"', page).group(1)
with owner.browser.open(BASE + image_path) as response:
    assert response.headers.get_content_type() == "image/png" and response.read().startswith(b"\x89PNG")

_, page = borrower.get(f"index.php?q={stamp}&category=Electronics")
has(page, "1 item found")
has(page, "₹80.00 flat fee")
_, page = borrower.get(f"index.php?q={stamp}&category=Books")
has(page, "0 items found")

item_path = f"item.php?id={item_id}"
url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "message": "Missing payment choice", **schedule})
has(page, "Choose cash or UPI")
url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "payment_method": "upi", "message": "Need it for Friday lab.", **schedule})
has(page, "Request sent")
request_id = re.search(r'name="request_id" value="(\d+)"', page).group(1)

url, page = borrower.post("dashboard.php", {"action": "cancel", "request_id": request_id})
has(page, "Request cancelled")
url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "payment_method": "upi", "message": "Need it next week.", **schedule})
has(page, "Request sent")
request_id = re.search(r'name="request_id" value="(\d+)"', page).group(1)
url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "payment_method": "upi", "message": "Duplicate request", **schedule})
has(page, "already have an active request")

url, page = owner.post("dashboard.php", {"action": "decline", "request_id": request_id})
has(page, "Request declined")
url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "payment_method": "upi", "message": "May I try again?", **schedule})
has(page, "Request sent")
request_id = re.search(r'name="request_id" value="(\d+)"', page).group(1)
url, page = owner.post(f"edit.php?id={item_id}", {
    "action": "edit_item", "item_id": item_id, "title": title,
    "category": "Electronics", "description": "For first-year lab work. Includes a case.",
    "location": "Library entrance", "fee": "95.00"
})
has(page, "₹95.00")
url, page = owner.post("dashboard.php", {"action": "approve", "request_id": request_id})
has(page, "Request approved")
has(page, return_time.strftime("%d %b %Y"))
has(page, "₹80.00")
url, page = borrower.post("dashboard.php", {"action": "mark_paid", "request_id": request_id})
has(page, "This payment cannot be marked received")
url, page = owner.post("dashboard.php", {"action": "mark_paid", "request_id": request_id})
has(page, "Payment marked received")
has(page, "Received")
_, page = borrower.get("dashboard.php")
has(page, return_time.strftime("%d %b %Y"))
has(page, "₹80.00")
has(page, "Received")
_, page = owner.get(f"index.php?q={stamp}")
has(page, "0 items found")
url, page = owner.post("dashboard.php", {"action": "return", "request_id": request_id})
has(page, "available again")
_, page = owner.get(f"index.php?q={stamp}")
has(page, "1 item found")

url, page = borrower.post(item_path, {"action": "request", "item_id": item_id, "payment_method": "upi", "message": "Borrowing it again.", **schedule})
has(page, "Request sent")

url, page = owner.post("add.php", {
    "action": "add_item", "title": "Spare Lab Coat", "category": "Lab",
    "description": "Clean, medium size.", "location": "Hostel lobby", "fee": "0.00"
})
second_id = re.search(r"item.php\?id=(\d+)", url).group(1)
url, page = owner.post("dashboard.php", {"action": "delete_item", "item_id": second_id})
has(page, "Listing removed")
_, page = owner.get(f"item.php?id={second_id}")
has(page, "Item not found")

hourly_title = f"Camera Tripod {stamp}"
url, page = owner.post("add.php", {
    "action": "add_item", "title": hourly_title, "category": "Other",
    "description": "Useful for class filming.", "location": "Library entrance",
    "pricing_mode": "hourly", "fee": "0.00", "hourly_rate": "12.50", "minimum_hours": "3"
})
hourly_id = re.search(r"item.php\?id=(\d+)", url).group(1)
has(page, "₹12.50 per hour")
has(page, "3 hours")
short_schedule = {"start_at": schedule["start_at"], "end_at": (pickup + timedelta(hours=2)).strftime("%Y-%m-%dT%H:%M")}
url, page = borrower.post(f"item.php?id={hourly_id}", {
    "action": "request", "item_id": hourly_id, "payment_method": "cash",
    "message": "Too short", **short_schedule
})
has(page, "Choose at least 3 hours")
hourly_schedule = {"start_at": schedule["start_at"], "end_at": (pickup + timedelta(hours=3, minutes=15)).strftime("%Y-%m-%dT%H:%M")}
url, page = borrower.post(f"item.php?id={hourly_id}", {
    "action": "request", "item_id": hourly_id, "payment_method": "cash",
    "message": "Need it for filming", **hourly_schedule
})
has(page, "Request sent")
has(page, "₹50.00")
has(page, "4 billed hours")
hourly_request_id = re.search(re.escape(hourly_title) + r'.*?name="request_id" value="(\d+)"', page, re.S).group(1)
url, page = owner.post(f"edit.php?id={hourly_id}", {
    "action": "edit_item", "item_id": hourly_id, "title": hourly_title,
    "category": "Other", "description": "Useful for class filming.",
    "location": "Library entrance", "pricing_mode": "hourly", "fee": "0.00",
    "hourly_rate": "20.00", "minimum_hours": "3"
})
has(page, "₹20.00 per hour")
url, page = owner.post("dashboard.php", {"action": "approve", "request_id": hourly_request_id})
has(page, "Request approved")
has(page, "₹50.00")
has(page, "4 billed hours")

url, _ = owner.post("dashboard.php", {"action": "logout"})
assert "index.php" in url
url, page = owner.post("account.php", {"action": "login", "email": f"owner{stamp}@example.test", "password": "wrongpass"})
has(page, "Email or password is incorrect")
url, page = owner.post("account.php", {"action": "login", "email": f"owner{stamp}@example.test", "password": "testpass123"})
assert "dashboard.php" in url
has(page, "Hello, Owner Test")

print("PASS: two-account flow, photos, owner-only edits, flat and hourly price snapshots, minimum duration, hour rounding, payment status, return dates, reuse, and removal")

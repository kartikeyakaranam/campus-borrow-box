USE borrow_box;
ALTER TABLE items ADD COLUMN image_filename VARCHAR(80) NULL;
ALTER TABLE requests ADD COLUMN due_date DATE NULL;

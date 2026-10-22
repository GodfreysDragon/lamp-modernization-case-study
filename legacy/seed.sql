-- Sample data. It deliberately includes the drift you find in real 10+ year old databases:
-- inconsistent status casing/whitespace, zero dates, empty due dates, soft-delete flags,
-- and orphaned rows left behind by hard deletes.

INSERT INTO users (id, username, password, full_name, email, is_admin, created) VALUES
(1, 'admin', MD5('admin123'), 'Dave Patterson', 'dave@acme-support.test', 1, '2009-02-11 09:00:00'),
(2, 'sarah', MD5('sarah2010'), 'Sarah Kim', 'sarah@acme-support.test', 0, '2010-06-01 08:30:00'),
(3, 'mike', MD5('password'), 'Mike Ortega', 'mike@acme-support.test', 0, '0000-00-00 00:00:00');

INSERT INTO customers (id, name, company, email, phone, notes, created) VALUES
(1, 'Linda Hart', 'Hart & Sons Plumbing', 'linda@hartplumbing.test', '555-0101', 'Prefers phone calls', '2009-03-02 10:00:00'),
(2, 'Raj Patel', 'Patel Dental Group', 'raj@pateldental.test', '(555) 0102', NULL, '2010-01-15 14:22:00'),
(3, 'Tom O''Brien', 'O''Brien Logistics', 'TOM@OBRIENLOGISTICS.TEST', '555.0103', 'VIP - net 30', '2011-07-19 16:05:00');

INSERT INTO tickets (id, customer_id, assigned_to, subject, body, status, priority, due_date, created, updated, deleted) VALUES
(1, 1, 2, 'Invoice PDF not generating', 'Clicking "Download invoice" gives a blank page.', 'open', 3, '03/15/2011', '2011-03-14 09:12:00', '2011-03-14 09:12:00', 0),
(2, 2, 3, 'Password reset email never arrives', 'Checked spam folder already.', 'Pending', 2, '03/18/2011', '2011-03-15 11:40:00', '2011-03-16 08:00:00', 0),
(3, 3, 2, 'Site down!!!', 'Whole portal returns 500.', 'closed ', 4, '03/16/2011', '2011-03-16 07:01:00', '2011-03-16 09:30:00', 0),
(4, 1, NULL, 'Add second login for office manager', '', 'open', 1, '', '2011-03-17 13:00:00', '0000-00-00 00:00:00', 0),
(5, 2, 2, 'Duplicate ticket, please ignore', 'dupe of #2', 'Closed', 2, '03/20/2011', '2011-03-17 15:00:00', '2011-03-17 15:05:00', 1),
(6, 7, 3, 'Billing question from old account', 'Customer record was deleted in 2010', 'open', 2, '12/30/2010', '2010-12-27 10:00:00', '2010-12-27 10:00:00', 0);

INSERT INTO comments (id, ticket_id, user_id, body, is_internal, created) VALUES
(1, 1, 2, 'Reproduced on Chrome and IE8.', 1, '2011-03-14 10:00:00'),
(2, 1, 1, 'Looks like the PDF lib ran out of memory. Bumping memory_limit.', 1, '2011-03-14 11:15:00'),
(3, 2, 3, 'Asked customer to whitelist our domain.', 0, '2011-03-16 08:00:00'),
(4, 99, 2, 'Comment on a ticket that was hard-deleted in 2010', 0, '2010-12-01 12:00:00');

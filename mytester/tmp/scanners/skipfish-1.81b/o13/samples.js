var mime_samples = [
  { 'mime': 'application/xhtml+xml', 'samples': [
    { 'url': 'http://localhost/generation/', 'dir': '_m0/0', 'linked': 2, 'len': 2714 },
    { 'url': 'http://localhost/generation/mytests/', 'dir': '_m0/1', 'linked': 2, 'len': 12802 },
    { 'url': 'http://localhost/generation/mytests/tests/', 'dir': '_m0/2', 'linked': 2, 'len': 204867 },
    { 'url': 'http://localhost/generation/mytests/tests/1/', 'dir': '_m0/3', 'linked': 2, 'len': 1550 } ]
  },
  { 'mime': 'text/html', 'samples': [
    { 'url': 'http://localhost/', 'dir': '_m1/0', 'linked': 2, 'len': 200 },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php', 'dir': '_m1/1', 'linked': 2, 'len': 374 },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php?info=name', 'dir': '_m1/2', 'linked': 2, 'len': 125 },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php?info=name--\x3e\x22\x3e\x27\x3e\x27\x22\x3csfi000010v160577\x3e', 'dir': '_m1/3', 'linked': 2, 'len': 258 } ]
  }
];

var issue_samples = [
  { 'severity': 3, 'type': 40402, 'samples': [
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php', 'extra': 'PHP error', 'dir': '_i0/0' },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php?info=name--\x3e\x22\x3e\x27\x3e\x27\x22\x3csfi000010v160577\x3e', 'extra': 'PHP error', 'dir': '_i0/1' } ]
  },
  { 'severity': 3, 'type': 40401, 'samples': [
    { 'url': 'http://localhost/', 'extra': 'PHP source', 'dir': '_i1/0' },
    { 'url': 'http://localhost/generation/', 'extra': 'Directory listing', 'dir': '_i1/1' },
    { 'url': 'http://localhost/generation/mytests/', 'extra': 'Directory listing', 'dir': '_i1/2' },
    { 'url': 'http://localhost/generation/mytests/tests/', 'extra': 'Directory listing', 'dir': '_i1/3' },
    { 'url': 'http://localhost/generation/mytests/tests/1/', 'extra': 'Directory listing', 'dir': '_i1/4' } ]
  },
  { 'severity': 0, 'type': 10901, 'samples': [
    { 'url': 'http://localhost/generation/mytests/tests/1/', 'extra': '', 'dir': '_i2/0' },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php', 'extra': '', 'dir': '_i2/1' } ]
  },
  { 'severity': 0, 'type': 10803, 'samples': [
    { 'url': 'http://localhost/', 'extra': '', 'dir': '_i3/0' },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php', 'extra': '', 'dir': '_i3/1' },
    { 'url': 'http://localhost/generation/mytests/tests/1/1.php?info=name', 'extra': '', 'dir': '_i3/2' } ]
  },
  { 'severity': 0, 'type': 10205, 'samples': [
    { 'url': 'http://localhost/sfi9876', 'extra': '', 'dir': '_i4/0' } ]
  },
  { 'severity': 0, 'type': 10202, 'samples': [
    { 'url': 'http://localhost/', 'extra': 'Apache/2.2.14 (Ubuntu)', 'dir': '_i5/0' } ]
  }
];


<?php

// This file previously held an abstract BaseCommandTest class.
// In Pest, shared setup is handled via beforeEach() in each test file directly,
// since PHPUnit's protected getMockBuilder() cannot be called from global scope.

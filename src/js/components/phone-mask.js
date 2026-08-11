const PHONE_PATTERN = '\\+7 \\(\\d{3}\\) \\d{3}-\\d{2}-\\d{2}';
const PHONE_LENGTH = 10;

function getNationalNumber(value) {
  const digits = value.replace(/\D/g, '');
  const nationalNumber = ['7', '8'].includes(digits[0]) ? digits.slice(1) : digits;

  return nationalNumber.slice(0, PHONE_LENGTH);
}

function formatPhone(value) {
  const number = getNationalNumber(value);

  if (!number) {
    return '';
  }

  let formatted = `+7 (${number.slice(0, 3)}`;

  if (number.length >= 3) {
    formatted += ')';
  }

  if (number.length > 3) {
    formatted += ` ${number.slice(3, 6)}`;
  }

  if (number.length > 6) {
    formatted += `-${number.slice(6, 8)}`;
  }

  if (number.length > 8) {
    formatted += `-${number.slice(8, 10)}`;
  }

  return formatted;
}

export function initPhoneMasks() {
  document.querySelectorAll('input[type="tel"]').forEach((input) => {
    input.inputMode = 'tel';
    input.maxLength = 18;
    input.pattern = PHONE_PATTERN;
    input.title = 'Введите телефон в формате +7 (999) 999-99-99';

    input.addEventListener('input', () => {
      input.value = formatPhone(input.value);
    });

    if (input.value) {
      input.value = formatPhone(input.value);
    }
  });
}

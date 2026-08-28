import { render, screen } from '@testing-library/react';
import { App } from './App';

test('la aplicación monta y anuncia su nombre', () => {
  render(<App />);
  expect(screen.getByRole('heading', { name: /last planner/i })).toBeInTheDocument();
});

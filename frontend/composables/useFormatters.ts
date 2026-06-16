export function useFormatters() {
  const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  })

  const integer = new Intl.NumberFormat('pt-BR')

  const currency = (value: number | string | undefined | null) => money.format(Number(value || 0))
  const number = (value: number | string | undefined | null) => integer.format(Number(value || 0))
  const percent = (value: number | string | undefined | null) => `${Number(value || 0).toFixed(2)}%`

  const shortDate = (value: string | undefined | null) => {
    if (!value) return '-'
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    }).format(new Date(value))
  }

  return { currency, number, percent, shortDate }
}


const STATION_CONTEXT_KEY = 'cienciasnet.station.context'

export function isStationContext(): boolean {
  return sessionStorage.getItem(STATION_CONTEXT_KEY) === 'active'
}

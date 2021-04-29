import * as React from "react";
import "./App.css";
import { TVChartContainer } from "./components/TVChartContainer/index";

export default function App(props) {
  const intervals = [1, 5, 15, 30, 60, 240, 1440];
  const tickers = [
    { key: 101, val: "EUR/USD" },
    { key: 102, val: "USD/CHF" },
    { key: 103, val: "GBP/USD" },
    { key: 104, val: "USD/JPY" },
    { key: 105, val: "AUD/USD" },
    { key: 106, val: "USD/CAD" },
  ];
  return (
    <TVChartContainer
      interval={
        intervals[Number(props.match.params.time_frame) - 1]
          ? intervals[Number(props.match.params.time_frame) - 1]
          : 15
      }
      symbol={
        tickers.find((ele) => ele.key == props.match.params.ticker)
          ? tickers.find((ele) => ele.key == props.match.params.ticker).val
          : "GBP/USD"
      }
      // timeOut={
      //   props.match.params.timeOut
      // }
      // validateId={
      //   props.match.params.validateId
      // }
      // currency={
      //   props.match.params.currency
      // }
      // type={
      //   props.match.params.type
      // }
      // lang={
      //   props.match.params.lang
      // }
    />
  );
}

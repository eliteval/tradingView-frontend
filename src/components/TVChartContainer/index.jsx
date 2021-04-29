import * as React from "react";
import "./index.css";
import Datafeed from "./api/";

function getLanguageFromURL() {
  const regex = new RegExp("[\\?&]lang=([^&#]*)");
  const results = regex.exec(window.location.search);
  return results === null
    ? null
    : decodeURIComponent(results[1].replace(/\+/g, " "));
}
function pintarLinea(widget, a1, b1, price1, price2) {
  widget.activeChart().createMultipointShape(
    [
      { time: a1 / 1000, price: price1, channel: "open" },
      { time: b1 / 1000, price: price2, channel: "open" },
    ],
    {
      shape: "trend_line",
      lock: true,
      disableSelection: true,
      disableSave: true,
      disableUndo: true,
    }
  );
}
function pintarFlecha(widget, a1, shape, text, price) {
  widget.activeChart().createShape(
    { time: a1 / 1000, price, channel: "open" },
    {
      shape: shape,
      text: text,
      lock: true,
      disableSelection: true,
      disableSave: true,
      disableUndo: true,
    }
  );
}
export class TVChartContainer extends React.PureComponent {
  constructor(props) {
    super(props);
    console.log(props);
  }
  static defaultProps = {
    symbol: "GBP/USD",
    interval: "1",
    containerId: "tv_chart_container",
    libraryPath: "/charting_library/",
    chartsStorageUrl: "https://saveload.tradingview.com",
    chartsStorageApiVersion: "1.1",
    clientId: "tradingview.com",
    userId: "public_user_id",
    fullscreen: false,
    autosize: false,
    studiesOverrides: {},
  };
  componentDidMount() {
    const widgetOptions = {
      debug: false,
      symbol: this.props.symbol,
      datafeed: Datafeed,
      interval: this.props.interval,
      container_id: this.props.containerId,
      library_path: this.props.libraryPath,
      locale: getLanguageFromURL() || "en",
      disabled_features: [],
      enabled_features: ["header_widget"],
      charts_storage_url: this.props.chartsStorageUrl,
      charts_storage_api_version: this.props.chartsStorageApiVersion,
      client_id: this.props.clientId,
      user_id: this.props.userId,
      fullscreen: this.props.fullscreen,
      autosize: false,
      overrides: {
        "mainSeriesProperties.showCountdown": true,
        "paneProperties.background": "#ecf9ff",
        "paneProperties.vertGridProperties.color": "#363c4e",
        "paneProperties.horzGridProperties.color": "#363c4e",
        "symbolWatermarkProperties.transparency": 90,
        "scalesProperties.textColor": "#AAA",
        "mainSeriesProperties.candleStyle.wickUpColor": "#336854",
        "mainSeriesProperties.candleStyle.wickDownColor": "#7f323f",
      },
      // timeframe: timeframe+"D"
    };

    const widget = (window.tvWidget = new window.TradingView.widget(
      widgetOptions
    ));
    const props = this.props;
    widget.onChartReady(() => {
      let dataLength = 0;
      let intervalFunction = setInterval(() => {
        while (dataLength < chart_data.length) {
          console.log(chart_data);
          if (chart_data[dataLength].end === true) {
            clearInterval(intervalFunction);
            break;
          }
          try {
            let y_axix = chart_data[dataLength].y_axix;
            let n = 0;
            if (chart_data[dataLength].status == "P") {
              if (y_axix.length > 0) {
                n = 0;
                let { operationsDetail } = chart_data[dataLength];
                for (var i = 0; i < y_axix.length; i++) {
                  if (operationsDetail[i].tipoOP != -1) {
                    const profit = parseFloat(operationsDetail[i].OrderProf);
                    let dateTime = operationsDetail[i].fechaFin.split(" ");                    
                    let date = dateTime[0].split("/");
                    console.log(dateTime);
                    console.log(date);
                    console.log(date[2] +
                      "-" +
                      (parseInt(date[1]) - 1) +
                      "-" +
                      date[0] +
                      "T" +
                      dateTime[1]);
                    const fechaFin = new Date(
                      date[2] +
                        "-" +
                        (parseInt(date[1]) - 1) +
                        "-" +
                        date[0] +
                        "T" +
                        dateTime[1]
                    );
                    dateTime = operationsDetail[i].fechaIni.split(" ");
                    date = dateTime[0].split("/");
                    console.log(dateTime);
                    console.log(date);
                    console.log(date[2] +
                      "-" +
                      (parseInt(date[1]) - 1) +
                      "-" +
                      date[0] +
                      "T" +
                      dateTime[1]);
                    const fechaIni = new Date(
                      date[2] +
                        "-" +
                        (parseInt(date[1]) - 1) +
                        "-" +
                        date[0] +
                        "T" +
                        dateTime[1]
                    );
                    const precioFin = parseFloat(operationsDetail[i].precioFin);
                    const precioIni = parseFloat(operationsDetail[i].precioIni);
                    console.log(fechaIni);
                    console.log(fechaFin);
                    console.log(fechaIni / 1000);
                    console.log(fechaFin / 1000);
                    const balance = parseFloat(y_axix[i]);
                    if (operationsDetail[i].tipoOP.indexOf("Sell") == 0) {
                      //sell
                      pintarLinea(
                        widget,
                        fechaIni,
                        fechaFin,
                        precioIni,
                        precioFin
                      );
                      pintarFlecha(
                        widget,
                        fechaIni,
                        "arrow_down",
                        "Sell",
                        precioIni
                      );
                      pintarFlecha(
                        widget,
                        fechaFin,
                        "arrow_up",
                        profit,
                        precioFin
                      );
                    } else {
                      //buy
                      pintarLinea(
                        widget,
                        fechaIni,
                        fechaFin,
                        precioIni,
                        precioFin
                      );
                      pintarFlecha(
                        widget,
                        fechaIni,
                        "arrow_up",
                        "Buy",
                        precioIni
                      );
                      pintarFlecha(
                        widget,
                        fechaFin,
                        "arrow_down",
                        profit,
                        precioFin
                      );
                    }
                  }
                }
                // console.log(dps);
              }
            } else if (chart_data[dataLength].status == "F") {
              if (y_axix != "") {
                // JFS - 01/07/2020 - bug no se muestra la ultima operacion
                //if (y_axis_points.length < data.validation_points && y_axix.length > 0) {
                if (y_axix.length > 0) {
                  let { operationsDetail } = chart_data[dataLength];
                  for (var i = 0; i < y_axix.length; i++) {
                    if (operationsDetail[i].tipoOP != -1) {
                      const profit = parseFloat(operationsDetail[i].OrderProf);
                      let dateTime = operationsDetail[i].fechaFin.split(" ");
                      let date = dateTime[0].split("/");
                      const fechaFin = new Date(
                        date[2] +
                          "-" +
                          (parseInt(date[1]) - 1) +
                          "-" +
                          date[0] +
                          "T" +
                          dateTime[1]
                      );
                      dateTime = operationsDetail[i].fechaIni.split(" ");
                      date = dateTime[0].split("/");
                      const fechaIni = new Date(
                        date[2] +
                          "-" +
                          (parseInt(date[1]) - 1) +
                          "-" +
                          date[0] +
                          "T" +
                          dateTime[1]
                      );

                      const precioFin = parseFloat(
                        operationsDetail[i].precioFin
                      );
                      const precioIni = parseFloat(
                        operationsDetail[i].precioIni
                      );
                      const balance = parseFloat(y_axix[i]);
                      if (operationsDetail[i].tipoOP.indexOf("Sell") == 0) {
                        //sell
                        pintarLinea(
                          widget,
                          fechaIni,
                          fechaFin,
                          precioIni,
                          precioFin
                        );
                        pintarFlecha(
                          widget,
                          fechaIni,
                          "arrow_down",
                          "Sell",
                          precioIni
                        );
                        pintarFlecha(
                          widget,
                          fechaFin,
                          "arrow_up",
                          profit,
                          precioFin
                        );
                      } else {
                        //buy
                        pintarLinea(
                          widget,
                          fechaIni,
                          fechaFin,
                          precioIni,
                          precioFin
                        );
                        pintarFlecha(
                          widget,
                          fechaIni,
                          "arrow_up",
                          "Buy",
                          precioIni
                        );
                        pintarFlecha(
                          widget,
                          fechaFin,
                          "arrow_down",
                          profit,
                          precioFin
                        );
                      }
                    }
                  }
                }
              }
            }
          } catch (err) {
            console.log(err);
          }
          dataLength++;
        }
      }, 3000);

      // setTimeout(() => {
      //   let updateInterval = 3000;
      //   let countt = 0;
      //   var updateChart = async function (validateId, currency) {
      //     //modification needed

      //   updateChart(props.validateId, props.currency);

      //   let intervalFunction = setInterval(function () {
      //     updateChart(props.validateId, props.currency);
      //   }, updateInterval);
      // }, 2000);
    });
  }

  render() {
    return <div id={this.props.containerId} className={"TVChartContainer"} />;
  }
}
